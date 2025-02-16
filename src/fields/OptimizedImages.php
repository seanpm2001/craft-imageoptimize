<?php
/**
 * Image Optimize plugin for Craft CMS
 *
 * Automatically optimize images after they've been transformed
 *
 * @link      https://nystudio107.com
 * @copyright Copyright (c) 2017 nystudio107
 */

namespace nystudio107\imageoptimize\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\elements\Asset;
use craft\fields\Matrix;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\models\FieldLayout;
use craft\models\Volume;
use craft\validators\ArrayValidator;
use GraphQL\Type\Definition\Type;
use nystudio107\imageoptimize\assetbundles\imageoptimize\ImageOptimizeAsset;
use nystudio107\imageoptimize\fields\OptimizedImages as OptimizedImagesField;
use nystudio107\imageoptimize\gql\types\generators\OptimizedImagesGenerator;
use nystudio107\imageoptimize\ImageOptimize;
use nystudio107\imageoptimize\models\OptimizedImage;
use ReflectionClass;
use ReflectionException;
use verbb\supertable\fields\SuperTableField;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\db\Schema;
use function is_array;
use function is_string;

/** @noinspection MissingPropertyAnnotationsInspection */

/**
 * @author    nystudio107
 * @package   ImageOptimize
 * @since     1.2.0
 */
class OptimizedImages extends Field
{
    // Constants
    // =========================================================================

    public const DEFAULT_ASPECT_RATIOS = [
        ['x' => 16, 'y' => 9],
    ];
    public const DEFAULT_IMAGE_VARIANTS = [
        [
            'width' => 1200,
            'useAspectRatio' => true,
            'aspectRatioX' => 16.0,
            'aspectRatioY' => 9.0,
            'retinaSizes' => ['1'],
            'quality' => 82,
            'format' => 'jpg',
        ],
    ];

    public const MAX_VOLUME_SUBFOLDERS = 30;

    // Public Properties
    // =========================================================================

    /**
     * @var array
     */
    public array $fieldVolumeSettings = [];

    /**
     * @var array
     */
    public array $ignoreFilesOfType = [];

    /**
     * @var bool
     */
    public bool $displayOptimizedImageVariants = true;

    /**
     * @var bool
     */
    public bool $displayDominantColorPalette = true;

    /**
     * @var bool
     */
    public bool $displayLazyLoadPlaceholderImages = true;

    /**
     * @var array
     */
    public array $variants = [];

    // Private Properties
    // =========================================================================

    /**
     * @var array
     */
    private array $aspectRatios = [];

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        // Unset any deprecated properties
        if (!empty($config)) {
            unset($config['transformMethod'], $config['imgixDomain']);
        }
        parent::__construct($config);
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'OptimizedImages';
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        // Handle cases where the plugin has been uninstalled
        if (ImageOptimize::$plugin !== null) {
            $settings = ImageOptimize::$plugin->getSettings();
            if ($settings) {
                if (empty($this->variants)) {
                    $this->variants = $settings->defaultVariants;
                }
                $this->aspectRatios = $settings->defaultAspectRatios;
            }
        }
        // If the user has deleted all default aspect ratios, provide a fallback
        if (empty($this->aspectRatios)) {
            $this->aspectRatios = self::DEFAULT_ASPECT_RATIOS;
        }
        // If the user has deleted all default variants, provide a fallback
        if (empty($this->variants)) {
            $this->variants = self::DEFAULT_IMAGE_VARIANTS;
        }
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();
        return array_merge($rules, [
            [
                [
                    'displayOptimizedImageVariants',
                    'displayDominantColorPalette',
                    'displayLazyLoadPlaceholderImages',
                ],
                'boolean',
            ],
            [
                [
                    'ignoreFilesOfType',
                    'variants',
                ],
                ArrayValidator::class
            ],
        ]);
    }

    /**
     * @inheritdoc
     * @since 1.6.2
     */
    public function getContentGqlType(): Type|array
    {
        $typeArray = OptimizedImagesGenerator::generateTypes($this);

        return [
            'name' => $this->handle,
            'description' => 'Optimized Images field',
            'type' => array_shift($typeArray),
        ];
    }

    /**
     * @inheritdoc
     * @since 1.7.0
     */
    public function useFieldset(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        parent::afterElementSave($element, $isNew);
        // Update our OptimizedImages Field data now that the Asset has been saved
        // If this element is propagating, we don't need to redo the image saving for each site
        if ($element instanceof Asset && $element->id !== null && !$element->propagating) {
            // If the scenario is Asset::SCENARIO_FILEOPS or Asset::SCENARIO_MOVE (if using Craft > v3.7.1) treat it as a new asset
            $scenario = $element->getScenario();
            $request = Craft::$app->getRequest();
            if ($isNew || $scenario === Asset::SCENARIO_FILEOPS || $scenario === Asset::SCENARIO_MOVE) {
                /**
                 * If this is a newly uploaded/created Asset, we can save the variants
                 * via a queue job to prevent it from blocking
                 */
                ImageOptimize::$plugin->optimizedImages->resaveAsset($element->id);
            } else if (!$request->isConsoleRequest && $request->getPathInfo() === 'assets/save-image') {
                /**
                 * If it's not a newly uploaded/created Asset, check to see if the image
                 * itself is being updated (via the ImageEditor). If so, update the
                 * variants immediately so the AssetSelectorHud displays the new images
                 */
                try {
                    ImageOptimize::$plugin->optimizedImages->updateOptimizedImageFieldData($this, $element);
                } catch (Exception $e) {
                    Craft::error($e->getMessage(), __METHOD__);
                }
            } else {
                ImageOptimize::$plugin->optimizedImages->resaveAsset($element->id);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $asset = null): mixed
    {
        // If we're passed in a string, assume it's JSON-encoded, and decode it
        if (is_string($value) && !empty($value)) {
            $value = Json::decodeIfJson($value);
        }
        // If we're passed in an array, make a model from it
        if (is_array($value)) {
            // Create a new OptimizedImage model and populate it
            $model = new OptimizedImage($value);
        } elseif ($value instanceof OptimizedImage) {
            $model = $value;
        } else {
            // Just create a new empty model
            $model = new OptimizedImage([]);
        }

        return $model;
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_TEXT;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): null|string
    {
        $namespace = Craft::$app->getView()->getNamespace();
        if (str_contains($namespace, Matrix::class) || str_contains($namespace, SuperTableField::class)) {
            // Render an error template, since the field only works when attached to an Asset
            try {
                return Craft::$app->getView()->renderTemplate(
                    'image-optimize/_components/fields/OptimizedImages_error',
                    [
                    ]
                );
            } catch (Exception $e) {
                Craft::error($e->getMessage(), __METHOD__);
            }
        }
        // Register our asset bundle
        try {
            Craft::$app->getView()->registerAssetBundle(ImageOptimizeAsset::class);
        } catch (InvalidConfigException $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }

        try {
            $reflect = new ReflectionClass($this);
            $thisId = $reflect->getShortName();
        } catch (ReflectionException $e) {
            Craft::error($e->getMessage(), __METHOD__);
            $thisId = 0;
        }
        // Get our id and namespace
        $id = Html::id($thisId);
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);
        $namespacePrefix = Craft::$app->getView()->namespaceInputName($thisId);
        $sizesWrapperId = Craft::$app->getView()->namespaceInputId('sizes-wrapper');
        $view = Craft::$app->getView();
        $view->registerJs(
            'document.addEventListener("vite-script-loaded", function (e) {' .
            'if (e.detail.path === "../src/web/assets/src/js/OptimizedImagesField.js") {' .
            'new Craft.OptimizedImagesInput(' .
            '"' . $namespacedId . '", ' .
            '"' . $namespacePrefix . '",' .
            '"' . $sizesWrapperId . '"' .
            ');' .
            '}' .
            '});'
        );

        // Prep our aspect ratios
        $aspectRatios = [];
        $index = 1;
        foreach ($this->aspectRatios as $aspectRatio) {
            if ($index % 6 === 0) {
                $aspectRatio['break'] = true;
            }
            $aspectRatios[] = $aspectRatio;
            $index++;
        }
        $aspectRatio = ['x' => 2, 'y' => 2, 'custom' => true];
        $aspectRatios[] = $aspectRatio;
        // Get only the user-editable settings
        $settings = ImageOptimize::$plugin->getSettings();

        // Render the settings template
        try {
            return Craft::$app->getView()->renderTemplate(
                'image-optimize/_components/fields/OptimizedImages_settings',
                [
                    'field' => $this,
                    'settings' => $settings,
                    'aspectRatios' => $aspectRatios,
                    'id' => $id,
                    'name' => $this->handle,
                    'namespace' => $namespacedId,
                    'fieldVolumes' => $this->getFieldVolumeInfo($this->handle),
                ]
            );
        } catch (Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }

        return '';
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        if ($element instanceof Asset && $this->handle !== null) {
            /** @var Asset $element */
            // Register our asset bundle
            try {
                Craft::$app->getView()->registerAssetBundle(ImageOptimizeAsset::class);
            } catch (InvalidConfigException $e) {
                Craft::error($e->getMessage(), __METHOD__);
            }

            // Get our id and namespace
            $id = Html::id($this->handle);
            $nameSpaceId = Craft::$app->getView()->namespaceInputId($id);

            // Variables to pass down to our field JavaScript to let it namespace properly
            $jsonVars = [
                'id' => $id,
                'name' => $this->handle,
                'namespace' => $nameSpaceId,
                'prefix' => Craft::$app->getView()->namespaceInputId(''),
            ];
            $jsonVars = Json::encode($jsonVars);
            $view = Craft::$app->getView();
            $view->registerJs(
                'document.addEventListener("vite-script-loaded", function (e) {' .
                'if (e.detail.path === "../src/web/assets/src/js/OptimizedImagesField.js") {' .
                "$('#{$nameSpaceId}-field').ImageOptimizeOptimizedImages(" .
                $jsonVars .
                ");" .
                '}' .
                '});'
            );

            $settings = ImageOptimize::$plugin->getSettings();
            $createVariants = ImageOptimize::$plugin->optimizedImages->shouldCreateVariants($this, $element);

            // Render the input template
            try {
                return Craft::$app->getView()->renderTemplate(
                    'image-optimize/_components/fields/OptimizedImages_input',
                    [
                        'name' => $this->handle,
                        'value' => $value,
                        'variants' => $this->variants,
                        'field' => $this,
                        'settings' => $settings,
                        'elementId' => $element->id,
                        'format' => $element->getExtension(),
                        'id' => $id,
                        'nameSpaceId' => $nameSpaceId,
                        'createVariants' => $createVariants,
                    ]
                );
            } catch (Exception $e) {
                Craft::error($e->getMessage(), __METHOD__);
            }
        }

        // Render an error template, since the field only works when attached to an Asset
        try {
            return Craft::$app->getView()->renderTemplate(
                'image-optimize/_components/fields/OptimizedImages_error',
                [
                ]
            );
        } catch (Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }

        return '';
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns an array of asset volumes and their sub-folders
     *
     * @param string|null $fieldHandle
     *
     * @return array
     * @throws InvalidConfigException
     */
    protected function getFieldVolumeInfo(?string $fieldHandle): array
    {
        $result = [];
        if ($fieldHandle !== null) {
            $volumes = Craft::$app->getVolumes()->getAllVolumes();
            $assets = Craft::$app->getAssets();
            foreach ($volumes as $volume) {
                if (($volume instanceof Volume) && $this->volumeHasField($volume, $fieldHandle)) {
                    $tree = $assets->getFolderTreeByVolumeIds([$volume->id]);
                    $result[] = [
                        'name' => $volume->name,
                        'handle' => $volume->handle,
                        'subfolders' => $this->assembleSourceList($tree),
                    ];
                }
            }
        }
        // If there are too many sub-folders in an Asset volume, don't display them, return an empty array
        if (count($result) > self::MAX_VOLUME_SUBFOLDERS) {
            $result = [];
        }

        return $result;
    }

    /**
     * See if the passed $volume has an OptimizedImagesField with the handle $fieldHandle
     *
     * @param Volume $volume
     * @param string $fieldHandle
     *
     * @return bool
     */
    protected function volumeHasField(Volume $volume, string $fieldHandle): bool
    {
        $result = false;
        /** @var FieldLayout $fieldLayout */
        $fieldLayout = $volume->getFieldLayout();
        // Loop through the fields in the layout to see if there is an OptimizedImages field
        if ($fieldLayout) {
            $fields = $fieldLayout->getCustomFields();
            foreach ($fields as $field) {
                if ($field instanceof OptimizedImagesField && $field->handle === $fieldHandle) {
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * Transforms an asset folder tree into a source list.
     *
     * @param array $folders
     * @param bool $includeNestedFolders
     *
     * @return array
     */
    protected function assembleSourceList(array $folders, bool $includeNestedFolders = true): array
    {
        $sources = [];

        foreach ($folders as $folder) {
            $children = $folder->getChildren();
            foreach ($children as $child) {
                $sources[$child->name] = $child->name;
            }
        }

        return $sources;
    }
}
