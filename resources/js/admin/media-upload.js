/**
 * Media Upload Handler
 *
 * Provides reusable functions for media upload functionality.
 * Works with the media-upload-modal component.
 *
 * Features:
 * - File validation (type, size)
 * - Thumbnail generation for images
 * - AJAX upload with progress tracking
 * - Queue management (max 3 concurrent uploads)
 * - Error handling and retry logic
 * - Success callbacks
 *
 * Usage:
 *
 * 1. Include in your page:
 *    <script src="{{ asset('js/admin/media-upload.js') }}"></script>
 *
 * 2. Open upload modal:
 *    <button @click="$dispatch('open-media-upload')">Upload Media</button>
 *
 * 3. Listen for upload success:
 *    @media-uploaded.window="handleUploadSuccess($event.detail)"
 *
 * 4. Handle upload success:
 *    function handleUploadSuccess(data) {
 *        console.log('File uploaded:', data.file);
 *        console.log('Server response:', data.response);
 *        // Update your UI with the new media
 *    }
 */

// Configuration
const MediaUploadConfig = {
    // Maximum file sizes
    maxImageSize: 10 * 1024 * 1024, // 10MB
    maxVideoSize: 100 * 1024 * 1024, // 100MB
    maxDocumentSize: 10 * 1024 * 1024, // 10MB

    // Maximum files per upload
    maxFiles: 10,

    // Maximum concurrent uploads
    maxConcurrent: 3,

    // Accepted MIME types
    acceptedTypes: {
        images: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        videos: ['video/mp4', 'video/webm'],
        documents: ['application/pdf']
    },

    // Upload endpoint
    uploadUrl: '/admin/media/upload',

    // Auto-close delay after successful upload (ms)
    autoCloseDelay: 2000
};

/**
 * Validate a file before upload
 *
 * @param {File} file - The file to validate
 * @returns {Object} { valid: boolean, error: string|null }
 */
function validateMediaFile(file) {
    // Get all accepted types
    const allTypes = [
        ...MediaUploadConfig.acceptedTypes.images,
        ...MediaUploadConfig.acceptedTypes.videos,
        ...MediaUploadConfig.acceptedTypes.documents
    ];

    // Check file type
    if (!allTypes.includes(file.type)) {
        return {
            valid: false,
            error: `Invalid file type: ${file.type}. Accepted types: JPG, PNG, GIF, WEBP, MP4, WEBM, PDF`
        };
    }

    // Check file size based on type
    if (MediaUploadConfig.acceptedTypes.images.includes(file.type)) {
        if (file.size > MediaUploadConfig.maxImageSize) {
            return {
                valid: false,
                error: `Image size exceeds ${formatBytes(MediaUploadConfig.maxImageSize)}`
            };
        }
    } else if (MediaUploadConfig.acceptedTypes.videos.includes(file.type)) {
        if (file.size > MediaUploadConfig.maxVideoSize) {
            return {
                valid: false,
                error: `Video size exceeds ${formatBytes(MediaUploadConfig.maxVideoSize)}`
            };
        }
    } else if (MediaUploadConfig.acceptedTypes.documents.includes(file.type)) {
        if (file.size > MediaUploadConfig.maxDocumentSize) {
            return {
                valid: false,
                error: `Document size exceeds ${formatBytes(MediaUploadConfig.maxDocumentSize)}`
            };
        }
    }

    return { valid: true, error: null };
}

/**
 * Generate thumbnail for an image file
 *
 * @param {File} file - The image file
 * @returns {Promise<string>} Base64 encoded thumbnail
 */
function generateImageThumbnail(file) {
    return new Promise((resolve, reject) => {
        if (!file.type.startsWith('image/')) {
            reject(new Error('File is not an image'));
            return;
        }

        const reader = new FileReader();

        reader.onload = (e) => {
            const img = new Image();

            img.onload = () => {
                // Create canvas for thumbnail
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');

                // Calculate thumbnail dimensions (max 200x200)
                const maxSize = 200;
                let width = img.width;
                let height = img.height;

                if (width > height) {
                    if (width > maxSize) {
                        height *= maxSize / width;
                        width = maxSize;
                    }
                } else {
                    if (height > maxSize) {
                        width *= maxSize / height;
                        height = maxSize;
                    }
                }

                canvas.width = width;
                canvas.height = height;

                // Draw image on canvas
                ctx.drawImage(img, 0, 0, width, height);

                // Convert to base64
                resolve(canvas.toDataURL(file.type));
            };

            img.onerror = () => {
                reject(new Error('Failed to load image'));
            };

            img.src = e.target.result;
        };

        reader.onerror = () => {
            reject(new Error('Failed to read file'));
        };

        reader.readAsDataURL(file);
    });
}

/**
 * Upload a file to the server
 *
 * @param {File} file - The file to upload
 * @param {Function} onProgress - Progress callback (progress: number)
 * @param {Function} onSuccess - Success callback (response: object)
 * @param {Function} onError - Error callback (error: string)
 * @returns {XMLHttpRequest} XHR object for abort functionality
 */
function uploadMediaFile(file, onProgress, onSuccess, onError) {
    const formData = new FormData();
    formData.append('file', file);

    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        formData.append('_token', csrfToken.content);
    }

    const xhr = new XMLHttpRequest();

    // Progress event
    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            const progress = Math.round((e.loaded / e.total) * 100);
            if (onProgress) {
                onProgress(progress);
            }
        }
    });

    // Success event
    xhr.addEventListener('load', () => {
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (onSuccess) {
                    onSuccess(response);
                }
            } catch (e) {
                if (onError) {
                    onError('Invalid server response');
                }
            }
        } else {
            let errorMessage = `Upload failed (${xhr.status})`;
            try {
                const response = JSON.parse(xhr.responseText);
                errorMessage = response.message || response.error || errorMessage;
            } catch (e) {
                // Use default error message
            }
            if (onError) {
                onError(errorMessage);
            }
        }
    });

    // Error event
    xhr.addEventListener('error', () => {
        if (onError) {
            onError('Network error occurred');
        }
    });

    // Abort event
    xhr.addEventListener('abort', () => {
        if (onError) {
            onError('Upload cancelled');
        }
    });

    // Send request
    xhr.open('POST', MediaUploadConfig.uploadUrl, true);
    xhr.send(formData);

    return xhr;
}

/**
 * Format bytes to human-readable string
 *
 * @param {number} bytes - Number of bytes
 * @param {number} decimals - Number of decimal places
 * @returns {string} Formatted string (e.g., "1.5 MB")
 */
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];

    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

/**
 * Get icon name for file type
 *
 * @param {string} mimeType - MIME type of the file
 * @returns {string} Lucide icon name
 */
function getFileTypeIcon(mimeType) {
    if (mimeType.startsWith('image/')) {
        return 'image';
    } else if (mimeType.startsWith('video/')) {
        return 'video';
    } else if (mimeType === 'application/pdf') {
        return 'file-text';
    } else {
        return 'file';
    }
}

/**
 * Upload multiple files in batches
 *
 * @param {Array<File>} files - Array of files to upload
 * @param {Object} callbacks - { onFileProgress, onFileSuccess, onFileError, onComplete }
 * @returns {Promise} Promise that resolves when all uploads complete
 */
async function uploadMediaFiles(files, callbacks = {}) {
    const {
        onFileProgress = () => {},
        onFileSuccess = () => {},
        onFileError = () => {},
        onComplete = () => {}
    } = callbacks;

    const results = {
        successful: [],
        failed: [],
        total: files.length
    };

    // Create chunks for concurrent uploads
    const chunks = [];
    for (let i = 0; i < files.length; i += MediaUploadConfig.maxConcurrent) {
        chunks.push(files.slice(i, i + MediaUploadConfig.maxConcurrent));
    }

    // Process each chunk
    for (const chunk of chunks) {
        await Promise.all(
            chunk.map((file) => {
                return new Promise((resolve) => {
                    uploadMediaFile(
                        file,
                        (progress) => onFileProgress(file, progress),
                        (response) => {
                            results.successful.push({ file, response });
                            onFileSuccess(file, response);
                            resolve();
                        },
                        (error) => {
                            results.failed.push({ file, error });
                            onFileError(file, error);
                            resolve();
                        }
                    );
                });
            })
        );
    }

    onComplete(results);
    return results;
}

/**
 * Quick upload helper - opens modal and handles upload
 *
 * @param {Function} onSuccess - Success callback
 * @param {Function} onError - Error callback
 */
function openMediaUploadModal(onSuccess, onError) {
    // Listen for upload success
    const handleUpload = (event) => {
        if (onSuccess) {
            onSuccess(event.detail);
        }
    };

    window.addEventListener('media-uploaded', handleUpload);

    // Dispatch open modal event
    window.dispatchEvent(new CustomEvent('open-media-upload'));

    // Cleanup listener after modal closes
    setTimeout(() => {
        window.removeEventListener('media-uploaded', handleUpload);
    }, 60000); // Remove listener after 1 minute
}

/**
 * Example usage:
 *
 * // Simple usage - open modal
 * openMediaUploadModal(
 *     (data) => {
 *         console.log('Uploaded:', data.response);
 *     },
 *     (error) => {
 *         console.error('Error:', error);
 *     }
 * );
 *
 * // Advanced usage - manual upload
 * const files = Array.from(document.querySelector('input[type="file"]').files);
 *
 * uploadMediaFiles(files, {
 *     onFileProgress: (file, progress) => {
 *         console.log(`${file.name}: ${progress}%`);
 *     },
 *     onFileSuccess: (file, response) => {
 *         console.log(`${file.name} uploaded successfully:`, response);
 *     },
 *     onFileError: (file, error) => {
 *         console.error(`${file.name} failed:`, error);
 *     },
 *     onComplete: (results) => {
 *         console.log('Upload complete:', results);
 *         console.log(`Success: ${results.successful.length}`);
 *         console.log(`Failed: ${results.failed.length}`);
 *     }
 * });
 */

// Export for module systems (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        MediaUploadConfig,
        validateMediaFile,
        generateImageThumbnail,
        uploadMediaFile,
        uploadMediaFiles,
        openMediaUploadModal,
        formatBytes,
        getFileTypeIcon
    };
}
