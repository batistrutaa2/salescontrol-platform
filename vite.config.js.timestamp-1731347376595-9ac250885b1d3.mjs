// vite.config.js
import { defineConfig } from "file:///var/www/html/node_modules/vite/dist/node/index.js";
import laravel from "file:///var/www/html/node_modules/laravel-vite-plugin/dist/index.js";
import html from "file:///var/www/html/node_modules/@rollup/plugin-html/dist/es/index.js";
import { glob } from "file:///var/www/html/node_modules/glob/dist/esm/index.js";
function GetFilesArray(query) {
  return glob.sync(query);
}
var pageJsFiles = GetFilesArray("resources/assets/js/*.js");
var vendorJsFiles = GetFilesArray("resources/assets/vendor/js/*.js");
var LibsJsFiles = GetFilesArray("resources/assets/vendor/libs/**/*.js");
var CoreScssFiles = GetFilesArray("resources/assets/vendor/scss/**/!(_)*.scss");
var LibsScssFiles = GetFilesArray("resources/assets/vendor/libs/**/!(_)*.scss");
var LibsCssFiles = GetFilesArray("resources/assets/vendor/libs/**/*.css");
var FontsScssFiles = GetFilesArray("resources/assets/vendor/fonts/**/!(_)*.scss");
function libsWindowAssignment() {
  return {
    name: "libsWindowAssignment",
    transform(src, id) {
      if (id.includes("jkanban.js")) {
        return src.replace("this.jKanban", "window.jKanban");
      } else if (id.includes("vfs_fonts")) {
        return src.replaceAll("this.pdfMake", "window.pdfMake");
      }
    }
  };
}
var vite_config_default = defineConfig({
  plugins: [
    laravel({
      input: [
        "resources/css/app.css",
        "resources/assets/css/demo.css",
        "resources/js/app.js",
        "resources/assets/js/comercialkanban.js",
        "resources/assets/js/importmailing.js",
        ...pageJsFiles,
        ...vendorJsFiles,
        ...LibsJsFiles,
        "resources/js/laravel-user-management.js",
        // Processing Laravel User Management CRUD JS File
        ...CoreScssFiles,
        ...LibsScssFiles,
        ...LibsCssFiles,
        ...FontsScssFiles
      ],
      refresh: true
    }),
    html(),
    libsWindowAssignment()
  ]
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCIvdmFyL3d3dy9odG1sXCI7Y29uc3QgX192aXRlX2luamVjdGVkX29yaWdpbmFsX2ZpbGVuYW1lID0gXCIvdmFyL3d3dy9odG1sL3ZpdGUuY29uZmlnLmpzXCI7Y29uc3QgX192aXRlX2luamVjdGVkX29yaWdpbmFsX2ltcG9ydF9tZXRhX3VybCA9IFwiZmlsZTovLy92YXIvd3d3L2h0bWwvdml0ZS5jb25maWcuanNcIjtpbXBvcnQgeyBkZWZpbmVDb25maWcgfSBmcm9tICd2aXRlJztcbmltcG9ydCBsYXJhdmVsIGZyb20gJ2xhcmF2ZWwtdml0ZS1wbHVnaW4nO1xuaW1wb3J0IGh0bWwgZnJvbSAnQHJvbGx1cC9wbHVnaW4taHRtbCc7XG5pbXBvcnQgeyBnbG9iIH0gZnJvbSAnZ2xvYic7XG5cbi8qKlxuICogR2V0IEZpbGVzIGZyb20gYSBkaXJlY3RvcnlcbiAqIEBwYXJhbSB7c3RyaW5nfSBxdWVyeVxuICogQHJldHVybnMgYXJyYXlcbiAqL1xuZnVuY3Rpb24gR2V0RmlsZXNBcnJheShxdWVyeSkge1xuICByZXR1cm4gZ2xvYi5zeW5jKHF1ZXJ5KTtcbn1cbi8qKlxuICogSnMgRmlsZXNcbiAqL1xuLy8gUGFnZSBKUyBGaWxlc1xuY29uc3QgcGFnZUpzRmlsZXMgPSBHZXRGaWxlc0FycmF5KCdyZXNvdXJjZXMvYXNzZXRzL2pzLyouanMnKTtcblxuLy8gUHJvY2Vzc2luZyBWZW5kb3IgSlMgRmlsZXNcbmNvbnN0IHZlbmRvckpzRmlsZXMgPSBHZXRGaWxlc0FycmF5KCdyZXNvdXJjZXMvYXNzZXRzL3ZlbmRvci9qcy8qLmpzJyk7XG5cbi8vIFByb2Nlc3NpbmcgTGlicyBKUyBGaWxlc1xuY29uc3QgTGlic0pzRmlsZXMgPSBHZXRGaWxlc0FycmF5KCdyZXNvdXJjZXMvYXNzZXRzL3ZlbmRvci9saWJzLyoqLyouanMnKTtcblxuLyoqXG4gKiBTY3NzIEZpbGVzXG4gKi9cbi8vIFByb2Nlc3NpbmcgQ29yZSwgVGhlbWVzICYgUGFnZXMgU2NzcyBGaWxlc1xuY29uc3QgQ29yZVNjc3NGaWxlcyA9IEdldEZpbGVzQXJyYXkoJ3Jlc291cmNlcy9hc3NldHMvdmVuZG9yL3Njc3MvKiovIShfKSouc2NzcycpO1xuXG4vLyBQcm9jZXNzaW5nIExpYnMgU2NzcyAmIENzcyBGaWxlc1xuY29uc3QgTGlic1Njc3NGaWxlcyA9IEdldEZpbGVzQXJyYXkoJ3Jlc291cmNlcy9hc3NldHMvdmVuZG9yL2xpYnMvKiovIShfKSouc2NzcycpO1xuY29uc3QgTGlic0Nzc0ZpbGVzID0gR2V0RmlsZXNBcnJheSgncmVzb3VyY2VzL2Fzc2V0cy92ZW5kb3IvbGlicy8qKi8qLmNzcycpO1xuXG4vLyBQcm9jZXNzaW5nIEZvbnRzIFNjc3MgRmlsZXNcbmNvbnN0IEZvbnRzU2Nzc0ZpbGVzID0gR2V0RmlsZXNBcnJheSgncmVzb3VyY2VzL2Fzc2V0cy92ZW5kb3IvZm9udHMvKiovIShfKSouc2NzcycpO1xuXG4vLyBQcm9jZXNzaW5nIFdpbmRvdyBBc3NpZ25tZW50IGZvciBMaWJzIGxpa2UgakthbmJhbiwgcGRmTWFrZVxuZnVuY3Rpb24gbGlic1dpbmRvd0Fzc2lnbm1lbnQoKSB7XG4gIHJldHVybiB7XG4gICAgbmFtZTogJ2xpYnNXaW5kb3dBc3NpZ25tZW50JyxcblxuICAgIHRyYW5zZm9ybShzcmMsIGlkKSB7XG4gICAgICBpZiAoaWQuaW5jbHVkZXMoJ2prYW5iYW4uanMnKSkge1xuICAgICAgICByZXR1cm4gc3JjLnJlcGxhY2UoJ3RoaXMuakthbmJhbicsICd3aW5kb3cuakthbmJhbicpO1xuICAgICAgfSBlbHNlIGlmIChpZC5pbmNsdWRlcygndmZzX2ZvbnRzJykpIHtcbiAgICAgICAgcmV0dXJuIHNyYy5yZXBsYWNlQWxsKCd0aGlzLnBkZk1ha2UnLCAnd2luZG93LnBkZk1ha2UnKTtcbiAgICAgIH1cbiAgICB9XG4gIH07XG59XG5cbmV4cG9ydCBkZWZhdWx0IGRlZmluZUNvbmZpZyh7XG4gIHBsdWdpbnM6IFtcbiAgICBsYXJhdmVsKHtcbiAgICAgIGlucHV0OiBbXG4gICAgICAgICdyZXNvdXJjZXMvY3NzL2FwcC5jc3MnLFxuICAgICAgICAncmVzb3VyY2VzL2Fzc2V0cy9jc3MvZGVtby5jc3MnLFxuICAgICAgICAncmVzb3VyY2VzL2pzL2FwcC5qcycsXG4gICAgICAgICdyZXNvdXJjZXMvYXNzZXRzL2pzL2NvbWVyY2lhbGthbmJhbi5qcycsXG4gICAgICAgICdyZXNvdXJjZXMvYXNzZXRzL2pzL2ltcG9ydG1haWxpbmcuanMnLFxuICAgICAgICAuLi5wYWdlSnNGaWxlcyxcbiAgICAgICAgLi4udmVuZG9ySnNGaWxlcyxcbiAgICAgICAgLi4uTGlic0pzRmlsZXMsXG4gICAgICAgICdyZXNvdXJjZXMvanMvbGFyYXZlbC11c2VyLW1hbmFnZW1lbnQuanMnLCAvLyBQcm9jZXNzaW5nIExhcmF2ZWwgVXNlciBNYW5hZ2VtZW50IENSVUQgSlMgRmlsZVxuICAgICAgICAuLi5Db3JlU2Nzc0ZpbGVzLFxuICAgICAgICAuLi5MaWJzU2Nzc0ZpbGVzLFxuICAgICAgICAuLi5MaWJzQ3NzRmlsZXMsXG4gICAgICAgIC4uLkZvbnRzU2Nzc0ZpbGVzXG4gICAgICBdLFxuICAgICAgcmVmcmVzaDogdHJ1ZVxuICAgIH0pLFxuICAgIGh0bWwoKSxcbiAgICBsaWJzV2luZG93QXNzaWdubWVudCgpXG4gIF1cbn0pO1xuIl0sCiAgIm1hcHBpbmdzIjogIjtBQUF5TixTQUFTLG9CQUFvQjtBQUN0UCxPQUFPLGFBQWE7QUFDcEIsT0FBTyxVQUFVO0FBQ2pCLFNBQVMsWUFBWTtBQU9yQixTQUFTLGNBQWMsT0FBTztBQUM1QixTQUFPLEtBQUssS0FBSyxLQUFLO0FBQ3hCO0FBS0EsSUFBTSxjQUFjLGNBQWMsMEJBQTBCO0FBRzVELElBQU0sZ0JBQWdCLGNBQWMsaUNBQWlDO0FBR3JFLElBQU0sY0FBYyxjQUFjLHNDQUFzQztBQU14RSxJQUFNLGdCQUFnQixjQUFjLDRDQUE0QztBQUdoRixJQUFNLGdCQUFnQixjQUFjLDRDQUE0QztBQUNoRixJQUFNLGVBQWUsY0FBYyx1Q0FBdUM7QUFHMUUsSUFBTSxpQkFBaUIsY0FBYyw2Q0FBNkM7QUFHbEYsU0FBUyx1QkFBdUI7QUFDOUIsU0FBTztBQUFBLElBQ0wsTUFBTTtBQUFBLElBRU4sVUFBVSxLQUFLLElBQUk7QUFDakIsVUFBSSxHQUFHLFNBQVMsWUFBWSxHQUFHO0FBQzdCLGVBQU8sSUFBSSxRQUFRLGdCQUFnQixnQkFBZ0I7QUFBQSxNQUNyRCxXQUFXLEdBQUcsU0FBUyxXQUFXLEdBQUc7QUFDbkMsZUFBTyxJQUFJLFdBQVcsZ0JBQWdCLGdCQUFnQjtBQUFBLE1BQ3hEO0FBQUEsSUFDRjtBQUFBLEVBQ0Y7QUFDRjtBQUVBLElBQU8sc0JBQVEsYUFBYTtBQUFBLEVBQzFCLFNBQVM7QUFBQSxJQUNQLFFBQVE7QUFBQSxNQUNOLE9BQU87QUFBQSxRQUNMO0FBQUEsUUFDQTtBQUFBLFFBQ0E7QUFBQSxRQUNBO0FBQUEsUUFDQTtBQUFBLFFBQ0EsR0FBRztBQUFBLFFBQ0gsR0FBRztBQUFBLFFBQ0gsR0FBRztBQUFBLFFBQ0g7QUFBQTtBQUFBLFFBQ0EsR0FBRztBQUFBLFFBQ0gsR0FBRztBQUFBLFFBQ0gsR0FBRztBQUFBLFFBQ0gsR0FBRztBQUFBLE1BQ0w7QUFBQSxNQUNBLFNBQVM7QUFBQSxJQUNYLENBQUM7QUFBQSxJQUNELEtBQUs7QUFBQSxJQUNMLHFCQUFxQjtBQUFBLEVBQ3ZCO0FBQ0YsQ0FBQzsiLAogICJuYW1lcyI6IFtdCn0K
