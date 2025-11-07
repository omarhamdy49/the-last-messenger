/**
 * Media Picker JavaScript Module
 *
 * Provides a standalone JavaScript API for opening and controlling the media picker modal.
 * This can be used independently or alongside the Alpine.js implementation.
 *
 * @module MediaPicker
 */

(function (window) {
    'use strict';

    /**
     * MediaPicker class for managing media selection
     */
    class MediaPicker {
        constructor() {
            this.isInitialized = false;
            this.defaultOptions = {
                mode: 'single',       // 'single' or 'multiple'
                type: 'all',          // 'all', 'image', 'video'
                onSelect: null,       // Callback function
                onClose: null,        // Callback function
                collection: null,     // Filter by collection name
                maxSelection: null    // Max items for multiple mode
            };
        }

        /**
         * Initialize the media picker (called automatically on first use)
         */
        init() {
            if (this.isInitialized) return;

            // Listen for media selection events
            window.addEventListener('media-selected', (event) => {
                console.log('Media selected:', event.detail);
            });

            this.isInitialized = true;
        }

        /**
         * Open the media picker modal
         *
         * @param {Object} options - Configuration options
         * @param {string} options.mode - Selection mode: 'single' or 'multiple'
         * @param {string} options.type - Media type filter: 'all', 'image', 'video'
         * @param {Function} options.onSelect - Callback when media is selected
         * @param {Function} options.onClose - Callback when modal is closed
         * @param {string} options.collection - Filter by collection name
         * @param {number} options.maxSelection - Maximum number of items (multiple mode)
         *
         * @example
         * MediaPicker.open({
         *   mode: 'single',
         *   type: 'image',
         *   onSelect: (media) => {
         *     console.log('Selected:', media);
         *     document.getElementById('featured_image_id').value = media.id;
         *     document.getElementById('preview').src = media.url;
         *   }
         * });
         *
         * @example
         * MediaPicker.open({
         *   mode: 'multiple',
         *   type: 'all',
         *   maxSelection: 5,
         *   onSelect: (mediaArray) => {
         *     console.log('Selected items:', mediaArray);
         *     // Handle multiple items
         *   }
         * });
         */
        open(options = {}) {
            this.init();

            const config = { ...this.defaultOptions, ...options };

            // Dispatch event to Alpine.js component
            window.dispatchEvent(new CustomEvent('media-picker-open', {
                detail: config
            }));

            return this;
        }

        /**
         * Close the media picker modal
         */
        close() {
            // Dispatch close event
            window.dispatchEvent(new CustomEvent('media-picker-close'));
            return this;
        }

        /**
         * Get currently selected media items
         *
         * @returns {Array|Object} Selected media item(s)
         */
        getSelected() {
            // This will return the current selection from the Alpine.js component
            const alpineData = Alpine.store('mediaPicker');
            return alpineData ? alpineData.selectedItems : [];
        }

        /**
         * Helper: Format file size for display
         *
         * @param {number} bytes - File size in bytes
         * @returns {string} Formatted file size
         */
        static formatFileSize(bytes) {
            if (!bytes || bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        /**
         * Helper: Check if file is an image
         *
         * @param {string} mimeType - MIME type of the file
         * @returns {boolean}
         */
        static isImage(mimeType) {
            return mimeType && mimeType.startsWith('image/');
        }

        /**
         * Helper: Check if file is a video
         *
         * @param {string} mimeType - MIME type of the file
         * @returns {boolean}
         */
        static isVideo(mimeType) {
            return mimeType && mimeType.startsWith('video/');
        }

        /**
         * Helper: Get media type from MIME type
         *
         * @param {string} mimeType - MIME type
         * @returns {string} 'image', 'video', or 'other'
         */
        static getMediaType(mimeType) {
            if (this.isImage(mimeType)) return 'image';
            if (this.isVideo(mimeType)) return 'video';
            return 'other';
        }
    }

    /**
     * jQuery Plugin Integration (if jQuery is available)
     */
    if (window.jQuery) {
        window.jQuery.fn.mediaPicker = function (options) {
            return this.each(function () {
                const $el = window.jQuery(this);
                const picker = new MediaPicker();

                $el.on('click', function (e) {
                    e.preventDefault();

                    const config = {
                        ...options,
                        onSelect: function (selected) {
                            // Trigger jQuery event
                            $el.trigger('media-selected', [selected]);

                            // Call original callback if provided
                            if (options && options.onSelect) {
                                options.onSelect(selected);
                            }
                        }
                    };

                    picker.open(config);
                });
            });
        };
    }

    // Export as global singleton
    window.MediaPicker = new MediaPicker();

    // Also export the class for advanced usage
    window.MediaPickerClass = MediaPicker;

})(window);

/**
 * Usage Examples:
 *
 * 1. Basic single image selection:
 * ================================
 * document.getElementById('choose-image-btn').addEventListener('click', () => {
 *   MediaPicker.open({
 *     mode: 'single',
 *     type: 'image',
 *     onSelect: (media) => {
 *       document.getElementById('featured_image_id').value = media.id;
 *       document.getElementById('image-preview').src = media.url;
 *     }
 *   });
 * });
 *
 *
 * 2. Multiple media selection with limit:
 * ========================================
 * MediaPicker.open({
 *   mode: 'multiple',
 *   type: 'all',
 *   maxSelection: 10,
 *   onSelect: (mediaArray) => {
 *     mediaArray.forEach(media => {
 *       console.log(media.id, media.url);
 *     });
 *   }
 * });
 *
 *
 * 3. Video-only selection:
 * ========================
 * MediaPicker.open({
 *   mode: 'single',
 *   type: 'video',
 *   onSelect: (video) => {
 *     document.getElementById('video-url').value = video.url;
 *   }
 * });
 *
 *
 * 4. With jQuery:
 * ===============
 * $('#choose-media-btn').mediaPicker({
 *   mode: 'multiple',
 *   type: 'image',
 *   onSelect: function(selected) {
 *     console.log('Selected:', selected);
 *   }
 * });
 *
 * $('#choose-media-btn').on('media-selected', function(e, selected) {
 *   console.log('Media selected via jQuery event:', selected);
 * });
 *
 *
 * 5. Inline button with onclick:
 * ==============================
 * <button onclick="MediaPicker.open({
 *   mode: 'single',
 *   type: 'image',
 *   onSelect: (media) => {
 *     document.getElementById('thumbnail').value = media.id;
 *     document.getElementById('preview').src = media.url;
 *   }
 * })">
 *   Choose Image
 * </button>
 *
 *
 * 6. Alpine.js integration:
 * =========================
 * <div x-data="{ selectedMedia: null }">
 *   <button @click="MediaPicker.open({
 *     mode: 'single',
 *     type: 'image',
 *     onSelect: (media) => { selectedMedia = media; }
 *   })">
 *     Choose Image
 *   </button>
 *
 *   <div x-show="selectedMedia">
 *     <img :src="selectedMedia?.url" />
 *   </div>
 * </div>
 *
 *
 * 7. Form integration with hidden input:
 * ======================================
 * <form>
 *   <input type="hidden" name="featured_image_id" id="featured_image_id">
 *
 *   <button type="button" onclick="MediaPicker.open({
 *     mode: 'single',
 *     type: 'image',
 *     onSelect: (media) => {
 *       document.getElementById('featured_image_id').value = media.id;
 *       document.getElementById('preview-container').innerHTML =
 *         `<img src='${media.url}' class='w-32 h-32 object-cover rounded-lg' />`;
 *     }
 *   })">
 *     Choose Featured Image
 *   </button>
 *
 *   <div id="preview-container"></div>
 * </form>
 *
 *
 * 8. Gallery builder (multiple images):
 * =====================================
 * <button onclick="MediaPicker.open({
 *   mode: 'multiple',
 *   type: 'image',
 *   onSelect: (images) => {
 *     const gallery = document.getElementById('gallery');
 *     gallery.innerHTML = images.map(img =>
 *       `<div class='inline-block m-2'>
 *         <img src='${img.url}' class='w-24 h-24 object-cover rounded' />
 *         <input type='hidden' name='gallery_images[]' value='${img.id}' />
 *       </div>`
 *     ).join('');
 *   }
 * })">
 *   Build Gallery
 * </button>
 * <div id="gallery"></div>
 *
 *
 * 9. With error handling:
 * =======================
 * MediaPicker.open({
 *   mode: 'single',
 *   type: 'image',
 *   onSelect: (media) => {
 *     try {
 *       if (!media || !media.id) {
 *         throw new Error('Invalid media selection');
 *       }
 *       document.getElementById('image_id').value = media.id;
 *     } catch (error) {
 *       console.error('Error handling media selection:', error);
 *       alert('Failed to select media');
 *     }
 *   },
 *   onClose: () => {
 *     console.log('Media picker closed');
 *   }
 * });
 *
 *
 * 10. Programmatic close:
 * =======================
 * const btn = document.getElementById('open-picker');
 * btn.addEventListener('click', () => {
 *   MediaPicker.open({
 *     mode: 'single',
 *     onSelect: (media) => {
 *       console.log('Selected:', media);
 *       // Auto-close after selection (optional, already done by default)
 *       MediaPicker.close();
 *     }
 *   });
 * });
 *
 * // Close from elsewhere
 * document.getElementById('cancel-btn').addEventListener('click', () => {
 *   MediaPicker.close();
 * });
 */
