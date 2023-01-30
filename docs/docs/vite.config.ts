import {defineConfig} from 'vite'
import SitemapPlugin from 'rollup-plugin-sitemap'
import VitePressConfig from './.vitepress/config'
import {DefaultTheme} from "vitepress/types/default-theme";
import SidebarItem = DefaultTheme.SidebarItem;

const docsSiteBaseUrl = 'https://nystudio107.com'
const docsBaseUrl = new URL(VitePressConfig.base!, docsSiteBaseUrl).href.replace(/\/$/, '') + '/'
const siteMapRoutes = VitePressConfig.themeConfig?.sidebar?.map((group: SidebarItem) => {
  return group.items!.map((items: SidebarItem) => ({
    path: items.link!.replace(/^\/+/, ''),
    name: items.text
  }));
}).reduce((prev: SidebarItem[], curr: SidebarItem[]) => {
  return prev.concat(curr);
});

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    SitemapPlugin({
      baseUrl: docsBaseUrl,
      contentBase: './docs/.vitepress/dist',
      routes: siteMapRoutes,
    })
  ],
  server: {
    host: '0.0.0.0',
    port: parseInt(process.env.DOCS_DEV_PORT),
    strictPort: true,
  }
});
