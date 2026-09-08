import DropzoneModule from 'dropzone/dist/dropzone';

// Dropzone 5 is distributed as a UMD bundle. Depending on the bundler interop,
// its constructor can be nested under `default`.
const Dropzone = DropzoneModule.default ?? DropzoneModule;

// Disable auto-discovery so Dropzone instances are created manually
Dropzone.autoDiscover = false;

// Expose Dropzone globally and as a module export
try {
  window.Dropzone = Dropzone;
} catch (e) {
  // window is not available
}

export { Dropzone };
