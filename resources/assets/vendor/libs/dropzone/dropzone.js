import Dropzone from 'dropzone/dist/dropzone';

// Disable auto-discovery so Dropzone instances are created manually
Dropzone.autoDiscover = false;

// Expose Dropzone globally and as a module export
try {
  window.Dropzone = Dropzone;
} catch (e) {
  // window is not available
}

export { Dropzone };
