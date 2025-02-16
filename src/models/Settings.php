<?php
/**
 * ImageOptimize plugin for Craft CMS
 *
 * Automatically optimize images after they've been transformed
 *
 * @link      https://nystudio107.com
 * @copyright Copyright (c) 2017 nystudio107
 */

namespace nystudio107\imageoptimize\models;

use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\validators\ArrayValidator;
use nystudio107\imageoptimize\imagetransforms\CraftImageTransform;
use nystudio107\imageoptimize\imagetransforms\ImageTransformInterface;
use nystudio107\imageoptimize\imagetransforms\ImgixImageTransform;
use nystudio107\imageoptimize\imagetransforms\ThumborImageTransform;
use yii\behaviors\AttributeTypecastBehavior;

/**
 * ImageOptimize Settings model
 *
 * @author    nystudio107
 * @package   ImageOptimize
 * @since     1.0.0
 */
class Settings extends Model
{
    // Constants
    // =========================================================================

    protected const DEPRECATED_PROPERTIES = [
        'generatePlacholders',
        'transformMethod',
        'imgixDomain',
        'imgixApiKey',
        'imgixSecurityToken',
        'thumborBaseUrl',
        'thumborSecurityKey',
    ];

    // Public Properties
    // =========================================================================

    /**
     * @var string The image transform class to use for image transforms
     */
    public string $transformClass = CraftImageTransform::class;

    /**
     * @var array Settings for the image transform components
     */
    public array $imageTransformTypeSettings = [];

    /**
     * @var bool Should the image variants in an Asset Volume be automatically
     *      re-saved when saving an OptimizedImages field, saving an Asset
     *      Volume that has an OptimizedImages field in its layout, or saving
     *      the ImageOptimized settings. Set this to false only if you will be
     *      manually using the CLI console command to resave image variants
     */
    public bool $automaticallyResaveImageVariants = true;

    /**
     * @var bool Should image variant be created on Asset save (aka
     *      BeforePageLoad)
     */
    public bool $generateTransformsBeforePageLoad = true;

    /**
     * @var bool Set to false to disable all placeholder generation
     */
    public bool $generatePlaceholders = true;

    /**
     * @var bool Controls whether a dominant color palette should be created
     *      for image variants It takes a bit of time, so if you never plan to
     *      use it, you can turn it off
     */
    public bool $createColorPalette = true;

    /**
     * @var bool Controls whether SVG placeholder silhouettes should be created
     *      for image variants It takes a bit of time, so if you never plan to
     *      use them, you can turn it off
     */
    public bool $createPlaceholderSilhouettes = false;

    /**
     * @var bool Whether the placeholder silhouette SVGs should be capped at 32Kb in size
     */
    public bool $capSilhouetteSvgSize = true;

    /**
     * @var bool Controls whether retina images are automatically created with
     *      reduced quality as per
     *      https://www.netvlies.nl/blogs/retina-revolutie-follow
     */
    public bool $lowerQualityRetinaImageVariants = true;

    /**
     * @var bool Controls whether Optimized Image Variants are created that
     *      would be up-scaled to be larger than the original source image
     */
    public bool $allowUpScaledImageVariants = false;

    /**
     * @var bool Controls whether images scaled down >= 50% should be
     *      automatically sharpened
     */
    public bool $autoSharpenScaledImages = true;

    /**
     * @var int The amount an image needs to be scaled down for automatic sharpening
     *      to be applied
     */
    public int $sharpenScaledImagePercentage = 50;

    /**
     * @var bool Whether to allow limiting the creation of Optimized Image Variants
     *      for images by sub-folders
     */
    public bool $assetVolumeSubFolders = true;

    /**
     * @var ImageTransformInterface[] The default Image Transform type classes
     */
    public array $defaultImageTransformTypes = [
    ];

    /**
     * @var array Default aspect ratios
     */
    public array $defaultAspectRatios = [
        ['x' => 16, 'y' => 9],
        ['x' => 8, 'y' => 5],
        ['x' => 4, 'y' => 3],
        ['x' => 5, 'y' => 4],
        ['x' => 1, 'y' => 1],
        ['x' => 9, 'y' => 16],
        ['x' => 5, 'y' => 8],
        ['x' => 3, 'y' => 4],
        ['x' => 4, 'y' => 5],
    ];

    /**
     * @var array Default variants
     */
    public array $defaultVariants = [
        [
            'width' => 1200,
            'useAspectRatio' => true,
            'aspectRatioX' => 16.0,
            'aspectRatioY' => 9.0,
            'retinaSizes' => ['1'],
            'quality' => 82,
            'format' => 'jpg',
        ],
        [
            'width' => 992,
            'useAspectRatio' => true,
            'aspectRatioX' => 16.0,
            'aspectRatioY' => 9.0,
            'retinaSizes' => ['1'],
            'quality' => 82,
            'format' => 'jpg',
        ],
        [
            'width' => 768,
            'useAspectRatio' => true,
            'aspectRatioX' => 4.0,
            'aspectRatioY' => 3.0,
            'retinaSizes' => ['1'],
            'quality' => 60,
            'format' => 'jpg',
        ],
        [
            'width' => 576,
            'useAspectRatio' => true,
            'aspectRatioX' => 4.0,
            'aspectRatioY' => 3.0,
            'retinaSizes' => ['1'],
            'quality' => 60,
            'format' => 'jpg',
        ],
    ];

    /**
     * @var array Active image processors
     */
    public array $activeImageProcessors = [
        'jpg' => [
            'jpegoptim',
        ],
        'png' => [
            'optipng',
        ],
        'svg' => [
            'svgo',
        ],
        'gif' => [
            'gifsicle',
        ],
    ];

    /**
     * @var array Active image variant creators
     */
    public array $activeImageVariantCreators = [
        'jpg' => [
            'cwebp',
        ],
        'png' => [
            'cwebp',
        ],
        'gif' => [
            'cwebp',
        ],
    ];

    /**
     * @var array Preset image processors
     */
    public array $imageProcessors = [
        // jpeg optimizers
        'jpegoptim' => [
            'commandPath' => '/usr/bin/jpegoptim',
            'commandOptions' => '-s',
            'commandOutputFileFlag' => '',
        ],
        'mozjpeg' => [
            'commandPath' => '/usr/bin/mozjpeg',
            'commandOptions' => '-optimize -copy none',
            'commandOutputFileFlag' => '-outfile',
        ],
        'jpegtran' => [
            'commandPath' => '/usr/bin/jpegtran',
            'commandOptions' => '-optimize -copy none',
            'commandOutputFileFlag' => '',
        ],
        // png optimizers
        'optipng' => [
            'commandPath' => '/usr/bin/optipng',
            'commandOptions' => '-o3 -strip all',
            'commandOutputFileFlag' => '',
        ],
        'pngcrush' => [
            'commandPath' => '/usr/bin/pngcrush',
            'commandOptions' => '-brute -ow',
            'commandOutputFileFlag' => '',
        ],
        'pngquant' => [
            'commandPath' => '/usr/bin/pngquant',
            'commandOptions' => '--strip --skip-if-larger',
            'commandOutputFileFlag' => '',
        ],
        // svg optimizers
        'svgo' => [
            'commandPath' => '/usr/bin/svgo',
            'commandOptions' => '',
            'commandOutputFileFlag' => '',
        ],
        // gif optimizers
        'gifsicle' => [
            'commandPath' => '/usr/bin/gifsicle',
            'commandOptions' => '-O3 -k 256',
            'commandOutputFileFlag' => '',
        ],
    ];

    public array $imageVariantCreators = [
        // webp variant creator
        'cwebp' => [
            'commandPath' => '/usr/bin/cwebp',
            'commandOptions' => '',
            'commandOutputFileFlag' => '-o',
            'commandQualityFlag' => '-q',
            'imageVariantExtension' => 'webp',
        ],
    ];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        // Unset any deprecated properties
        if (!empty($config)) {
            // Handle migrating old Imagix settings
            if (isset($config['imgixDomain'])) {
                $config['imageTransformTypeSettings'][ImgixImageTransform::class]['domain'] = $config['imgixDomain'];
            }
            if (isset($config['imgixApiKey'])) {
                $config['imageTransformTypeSettings'][ImgixImageTransform::class]['apiKey'] = $config['imgixApiKey'];
            }
            if (isset($config['imgixSecurityToken'])) {
                $config['imageTransformTypeSettings'][ImgixImageTransform::class]['securityToken'] = $config['imgixSecurityToken'];
            }
            // Handle migrating old Thumbor settings
            if (isset($config['thumborBaseUrl'])) {
                $config['imageTransformTypeSettings'][ThumborImageTransform::class]['baseUrl'] = $config['thumborBaseUrl'];
            }
            if (isset($config['thumborSecurityKey'])) {
                $config['imageTransformTypeSettings'][ThumborImageTransform::class]['securityKey'] = $config['thumborSecurityKey'];
            }
            // Remove deprecated properties
            foreach (self::DEPRECATED_PROPERTIES as $prop) {
                unset($config[$prop]);
            }
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            ['transformClass', 'string'],
            ['transformClass', 'default', 'value' => CraftImageTransform::class],
            [
                [
                    'automaticallyResaveImageVariants',
                    'generateTransformsBeforePageLoad',
                    'createColorPalette',
                    'createPlaceholderSilhouettes',
                    'capSilhouetteSvgSize',
                    'lowerQualityRetinaImageVariants',
                    'allowUpScaledImageVariants',
                    'autoSharpenScaledImages',
                    'assetVolumeSubFolders',
                ],
                'boolean',
            ],
            ['sharpenScaledImagePercentage', 'integer', 'min' => 0, 'max' => 100],
            [
                [
                    'defaultVariants',
                    'activeImageProcessors',
                    'activeImageVariantCreators',
                    'imageProcessors',
                    'imageVariantCreators',
                ],
                'required',
            ],
            [
                [
                    'imageTransformTypeSettings',
                    'defaultImageTransformTypes',
                    'defaultVariants',
                    'activeImageProcessors',
                    'activeImageVariantCreators',
                    'imageProcessors',
                    'imageVariantCreators',
                ],
                ArrayValidator::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        // Only return user-editable settings
        return [
            'transformClass',
            'imageTransformTypeSettings',
            'createColorPalette',
            'createPlaceholderSilhouettes',
            'capSilhouetteSvgSize',
            'lowerQualityRetinaImageVariants',
            'allowUpScaledImageVariants',
            'autoSharpenScaledImages',
            'sharpenScaledImagePercentage',
            'assetVolumeSubFolders',
        ];
    }

    /**
     * @return array
     */
    public function behaviors(): array
    {
        return [
            'typecast' => [
                'class' => AttributeTypecastBehavior::class,
                // 'attributeTypes' will be composed automatically according to `rules()`
            ],
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => [
                ],
            ]
        ];
    }
}
