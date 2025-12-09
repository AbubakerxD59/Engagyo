 <style>
    /* --- Accounts Container Styling --- */
    .accounts-container {
        /* max-height: 280px; */
        /* overflow-y: auto; */
        overflow-x: hidden;
        padding: 10px 5px;
        margin-bottom: 10px;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
    }

     .accounts-container::-webkit-scrollbar {
         width: 6px;
     }

     .accounts-container::-webkit-scrollbar-track {
         background: #f1f1f1;
         border-radius: 3px;
     }

     .accounts-container::-webkit-scrollbar-thumb {
         background: #c1c1c1;
         border-radius: 3px;
     }

     .accounts-container::-webkit-scrollbar-thumb:hover {
         background: #a1a1a1;
     }

    .accounts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 10px;
        width: 100%;
        box-sizing: border-box;
    }

     .account-card {
         background: #fff;
         border: 2px solid #e9ecef;
         border-radius: 10px;
         padding: 10px 12px;
         cursor: pointer;
         transition: all 0.2s ease;
         position: relative;
     }

     .account-card:hover {
         border-color: #dee2e6;
         box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
         transform: translateY(-2px);
     }

     .account-card.active {
         border-color: #28a745;
         background: linear-gradient(135deg, #f0fff4 0%, #fff 100%);
         box-shadow: 0 2px 8px rgba(40, 167, 69, 0.15);
     }

     .account-card.active::after {
         content: '\f00c';
         font-family: 'Font Awesome 5 Free';
         font-weight: 900;
         position: absolute;
         top: -6px;
         right: -6px;
         background: #28a745;
         color: #fff;
         width: 18px;
         height: 18px;
         border-radius: 50%;
         font-size: 10px;
         display: flex;
         align-items: center;
         justify-content: center;
     }

     .account-card-inner {
         display: flex;
         align-items: center;
         gap: 10px;
     }

     .account-avatar {
         position: relative;
         flex-shrink: 0;
     }

     .account-avatar img {
         width: 40px;
         height: 40px;
         border-radius: 50%;
         object-fit: cover;
         border: 2px solid #e9ecef;
     }

     .platform-badge {
         position: absolute;
         bottom: -2px;
         right: -2px;
         width: 18px;
         height: 18px;
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         font-size: 9px;
         color: #fff;
         border: 2px solid #fff;
     }

     .platform-badge.facebook {
         background: #1877F2;
     }

    .platform-badge.pinterest {
        background: #E60023;
    }

    .platform-badge.tiktok {
        background: #000000;
    }

    .account-details {
         display: flex;
         flex-direction: column;
         min-width: 0;
         flex: 1;
     }

     .account-details .account-name {
         font-size: 13px;
         font-weight: 600;
         color: #333;
         line-height: 1.3;
         white-space: nowrap;
         overflow: hidden;
         text-overflow: ellipsis;
     }

     .account-details .account-username {
         font-size: 11px;
         color: #888;
         line-height: 1.3;
         white-space: nowrap;
         overflow: hidden;
         text-overflow: ellipsis;
     }

     /* Responsive adjustments */
     @media (max-width: 576px) {
         .accounts-grid {
             grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
         }
     }

     /* --- Custom Skeleton Styling --- */

     /* Animation Definition */
     @keyframes pulse-slow {

         0%,
         100% {
             opacity: 1;
         }

         50% {
             opacity: .5;
         }
     }

     .animate-pulse-slow {
         animation: pulse-slow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
     }

     /* Card Container (Main wrapper) */
     .card-container {
         width: 100%;
         max-width: 40rem;
         /* max-w-xl equivalent */
         background-color: white;
         box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
         border-radius: 0.75rem;
         /* rounded-xl */
         position: relative;
     }

     /* Skeleton Wrapper (Inner content box) */
     .skeleton-wrapper {
         display: flex;
         padding: 1.5rem;
         /* p-6 */
         background-color: #F3F4F6;
         /* skeleton-bg */
         border-radius: 0.75rem;
         overflow: hidden;
         /* Ensure rounded corners clip content */
     }

     /* Left Column (Text area) */
     .content-col {
         flex-grow: 1;
         padding-right: 1.5rem;
         /* pr-6 */
     }

     /* Right Column (Image/Sidebar block) */
     .image-col {
         width: 25%;
         /* w-1/4 */
         flex-shrink: 0;
         position: relative;
     }

     /* Skeleton Bars (The actual pulsing lines) */
     .skeleton-bar {
         background-color: #E5E7EB;
         /* skeleton-bar */
         border-radius: 0.25rem;
         /* rounded-sm to rounded-md */
         margin-bottom: 1rem;
         /* space-y-4 converted to margin-bottom */
     }

     /* Skeleton Bar Dimensions */
     .bar-title {
         height: 1.5rem;
         width: 75%;
         border-radius: 0.375rem;
     }

     /* h-6 w-3/4 */
     .bar-full {
         height: 1rem;
         width: 100%;
     }

     .bar-medium {
         height: 1rem;
         width: 85%;
     }

     /* w-5/6 */
     .bar-short {
         height: 1rem;
         width: 33.333%;
     }

     /* w-1/3 */
     .image-placeholder {
         height: 100%;
         width: 100%;
         border-radius: 0.5rem;
     }

     /* Close Button Styling */
     .close-btn-placeholder {
         position: absolute;
         top: -0.75rem;
         /* -top-3 */
         right: -0.75rem;
         /* -right-3 */
         width: 1.5rem;
         /* w-6 */
         height: 1.5rem;
         /* h-6 */
         background-color: black;
         color: white;
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         font-weight: bold;
         font-size: 0.75rem;
         /* text-xs */
         cursor: pointer;
         border: none;
         box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
     }

     /* Real Content Styling */
     .real-article-wrapper {
         display: flex;
         padding: 1.5rem;
         background-color: white;
         border-radius: 0.75rem;
         border: 1px solid #E5E7EB;
         box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
         color: #1F2937;
         opacity: 0;
         transition: opacity 1s;
     }

     .real-article-wrapper .title {
         font-size: 1.25rem;
         font-weight: bold;
         margin-bottom: 0.5rem;
         color: #4338CA;
         /* Indigo-700 equivalent */
     }

     .real-article-wrapper .summary {
         font-size: 0.875rem;
         color: #4B5563;
         line-height: 1.625;
     }

     .real-article-wrapper img {
         width: 100%;
         height: auto;
         border-radius: 0.5rem;
         box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
     }

     /* Image Lightbox */
     .image-lightbox {
         position: fixed;
         top: 0;
         left: 0;
         width: 100%;
         height: 100%;
         z-index: 9999;
         display: none;
         align-items: center;
         justify-content: center;
     }

     .image-lightbox.active {
         display: flex;
     }

     .lightbox-backdrop {
         position: absolute;
         top: 0;
         left: 0;
         width: 100%;
         height: 100%;
         background: rgba(0, 0, 0, 0.9);
         cursor: pointer;
     }

     .lightbox-content {
         position: relative;
         max-width: 90%;
         max-height: 90%;
         display: flex;
         flex-direction: column;
         align-items: center;
         animation: lightboxZoomIn 0.3s ease;
     }

     @keyframes lightboxZoomIn {
         from {
             opacity: 0;
             transform: scale(0.8);
         }
         to {
             opacity: 1;
             transform: scale(1);
         }
     }

     .lightbox-content img {
         max-width: 100%;
         max-height: 80vh;
         border-radius: 8px;
         box-shadow: 0 10px 50px rgba(0, 0, 0, 0.5);
         object-fit: contain;
     }

     .lightbox-close {
         position: absolute;
         top: -40px;
         right: -40px;
         width: 40px;
         height: 40px;
         border: none;
         background: rgba(255, 255, 255, 0.1);
         color: #fff;
         font-size: 20px;
         border-radius: 50%;
         cursor: pointer;
         transition: all 0.2s ease;
         display: flex;
         align-items: center;
         justify-content: center;
     }

     .lightbox-close:hover {
         background: rgba(255, 255, 255, 0.2);
         transform: scale(1.1);
     }

     .lightbox-caption {
         margin-top: 15px;
         color: #fff;
         font-size: 14px;
         text-align: center;
         max-width: 600px;
         line-height: 1.5;
     }

     /* Make post images clickable in DataTable */
     #postsTable .pinterest_card .image-container img.post-image,
     #postsTable .facebook_card .pronunciation-image-container img {
         cursor: zoom-in;
         transition: opacity 0.2s ease;
     }

     #postsTable .pinterest_card .image-container img.post-image:hover,
     #postsTable .facebook_card .pronunciation-image-container img:hover {
         opacity: 0.9;
     }

     /* Magnify icon on hover */
     #postsTable .pinterest_card .image-container,
     #postsTable .facebook_card .pronunciation-image-container {
         position: relative;
     }

     #postsTable .pinterest_card .image-container::before,
     #postsTable .facebook_card .pronunciation-image-container::before {
         content: '\f00e';
         font-family: 'Font Awesome 5 Free';
         font-weight: 900;
         position: absolute;
         top: 50%;
         left: 50%;
         transform: translate(-50%, -50%);
         width: 50px;
         height: 50px;
         background: rgba(0, 0, 0, 0.6);
         color: #fff;
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         font-size: 20px;
         opacity: 0;
         transition: opacity 0.2s ease;
         pointer-events: none;
         z-index: 10;
     }

     #postsTable .pinterest_card .image-container:hover::before,
     #postsTable .facebook_card .pronunciation-image-container:hover::before {
         opacity: 1;
     }

     @media (max-width: 768px) {
         .lightbox-close {
             top: 10px;
             right: 10px;
         }
     }

     /* Schedule Posts Grid Layout */
     .schedule-posts-grid {
         display: grid;
         grid-template-columns: repeat(3, 1fr);
         gap: 20px;
     }

     @media (max-width: 1200px) {
         .schedule-posts-grid {
             grid-template-columns: repeat(2, 1fr);
         }
     }

     @media (max-width: 768px) {
         .schedule-posts-grid {
             grid-template-columns: 1fr;
         }
     }

     /* Schedule Post Card Container */
     .schedule-post-card {
         background: #fff;
         border-radius: 12px;
         box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
         overflow: hidden;
         transition: all 0.3s ease;
         display: flex;
         flex-direction: column;
         height: 580px;
     }

     .schedule-post-card:hover {
         box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
         transform: translateY(-2px);
     }

     /* Post Preview Section */
     .schedule-post-card .post-preview {
         height: 320px;
         overflow: hidden;
         position: relative;
     }

     .schedule-post-card .post-preview::after {
         content: '';
         position: absolute;
         bottom: 0;
         left: 0;
         right: 0;
         height: 30px;
         background: linear-gradient(to bottom, transparent, rgba(255,255,255,0.9));
         pointer-events: none;
     }

     .schedule-post-card .post-preview .pinterest_card,
     .schedule-post-card .post-preview .facebook_card {
         margin: 0;
         border-radius: 0;
         box-shadow: none;
         height: 100%;
         overflow: hidden;
     }

     .schedule-post-card .post-preview .pinterest_card .image-container,
     .schedule-post-card .post-preview .facebook_card .pronunciation-image-container {
         max-height: 180px;
         overflow: hidden;
     }

     .schedule-post-card .post-preview .pinterest_card .image-container img,
     .schedule-post-card .post-preview .facebook_card .pronunciation-image-container img {
         width: 100%;
         height: 180px;
         object-fit: cover;
     }

     /* Post Meta Section */
     .schedule-post-card .post-meta {
         padding: 12px 15px;
         background: #f8f9fa;
         border-top: 1px solid #e9ecef;
         flex: 1;
         display: flex;
         flex-direction: column;
         min-height: 0;
         overflow-y: auto;
     }

     .schedule-post-card .post-meta::-webkit-scrollbar {
         width: 4px;
     }

     .schedule-post-card .post-meta::-webkit-scrollbar-thumb {
         background: #ddd;
         border-radius: 2px;
     }

     .post-meta-row {
         display: flex;
         align-items: center;
         justify-content: space-between;
         margin-bottom: 10px;
     }

     .post-meta-row:last-child {
         margin-bottom: 0;
     }

     /* Account Badge */
     .post-account-badge {
         display: inline-flex;
         align-items: center;
         gap: 8px;
         padding: 4px 10px;
         background: #fff;
         border-radius: 20px;
         border: 1px solid #e9ecef;
     }

     .post-account-badge img {
         width: 24px;
         height: 24px;
         border-radius: 50%;
         object-fit: cover;
     }

     .post-account-badge .platform-icon {
         width: 16px;
         height: 16px;
         border-radius: 50%;
         display: inline-flex;
         align-items: center;
         justify-content: center;
         font-size: 9px;
         color: #fff;
     }

     .post-account-badge .platform-icon.facebook { background: #1877F2; }
     .post-account-badge .platform-icon.pinterest { background: #E60023; }
     .post-account-badge .platform-icon.tiktok { background: #000000; }

     .post-account-badge .post-account-name {
         font-size: 12px;
         font-weight: 600;
         color: #333;
         max-width: 100px;
         overflow: hidden;
         text-overflow: ellipsis;
         white-space: nowrap;
     }

     /* Source Badge */
     .source-badge {
         display: inline-flex;
         align-items: center;
         gap: 4px;
         padding: 3px 8px;
         border-radius: 4px;
         font-size: 11px;
         font-weight: 500;
     }

     .source-badge.rss {
         background: #fff3cd;
         color: #856404;
     }

     .source-badge.api {
         background: #e3f2fd;
         color: #1976d2;
     }

     .source-badge.manual {
         background: #e8f5e9;
         color: #2e7d32;
     }

     .source-badge i {
         font-size: 10px;
     }

     /* Date/Time Info */
     .datetime-info {
         font-size: 11px;
         color: #666;
     }

     .datetime-info .label {
         color: #999;
         margin-right: 4px;
     }

     .datetime-info .value {
         font-weight: 500;
         color: #333;
     }

     /* Status Badges */
     .status-badge {
         display: inline-flex;
         align-items: center;
         gap: 4px;
         padding: 4px 10px;
         border-radius: 4px;
         font-size: 11px;
         font-weight: 600;
         text-transform: uppercase;
     }

     .status-badge.pending {
         background: #fff3cd;
         color: #856404;
     }

     .status-badge.published {
         background: #d4edda;
         color: #155724;
     }

     .status-badge.failed {
         background: #f8d7da;
         color: #721c24;
     }

     /* Published At */
     .published-at {
         font-size: 10px;
         color: #28a745;
         background: #d4edda;
         padding: 2px 6px;
         border-radius: 3px;
         margin-top: 4px;
         display: inline-block;
     }

     /* Response Section */
     .response-section {
         margin-top: 8px;
         padding: 8px;
         background: #fff;
         border-radius: 6px;
         border: 1px solid #e9ecef;
         max-height: 60px;
         overflow-y: auto;
     }

     .response-section::-webkit-scrollbar {
         width: 3px;
     }

     .response-section::-webkit-scrollbar-thumb {
         background: #ddd;
         border-radius: 2px;
     }

     .response-section .response-label {
         font-size: 10px;
         color: #999;
         text-transform: uppercase;
         margin-bottom: 2px;
     }

     .response-section .response-text {
         font-size: 11px;
         color: #333;
         word-break: break-word;
         line-height: 1.3;
     }

     .response-section .response-text.success {
         color: #28a745;
     }

     .response-section .response-text.error {
         color: #dc3545;
     }

     /* Action Buttons */
     .post-actions-bar {
         display: flex;
         gap: 8px;
         margin-top: auto;
         padding-top: 10px;
         border-top: 1px solid #e9ecef;
     }

     .post-actions-bar .btn {
         flex: 1;
         padding: 6px 10px;
         font-size: 12px;
         border-radius: 6px;
     }

     /* Empty State */
     .empty-state {
         grid-column: 1 / -1;
         text-align: center;
         padding: 60px 20px;
         color: #999;
     }

     .empty-state i {
         font-size: 48px;
         margin-bottom: 15px;
         opacity: 0.5;
     }

     /* Pagination Styles */
     .pagination-info {
         font-size: 13px;
     }

     .pagination .page-item .page-link {
         border-radius: 6px;
         margin: 0 2px;
         border: none;
         color: #666;
     }

     .pagination .page-item.active .page-link {
         background: var(--theme-color);
         color: #fff;
     }

     .pagination .page-item.disabled .page-link {
         color: #ccc;
     }

     /* Make post images clickable in Grid */
     .schedule-post-card .pinterest_card .image-container img.post-image,
     .schedule-post-card .facebook_card .pronunciation-image-container img {
         cursor: zoom-in;
         transition: opacity 0.2s ease;
     }

     .schedule-post-card .pinterest_card .image-container img.post-image:hover,
     .schedule-post-card .facebook_card .pronunciation-image-container img:hover {
         opacity: 0.9;
     }

     /* Magnify icon on hover for Grid */
     .schedule-post-card .pinterest_card .image-container,
     .schedule-post-card .facebook_card .pronunciation-image-container {
         position: relative;
     }

     .schedule-post-card .pinterest_card .image-container::before,
     .schedule-post-card .facebook_card .pronunciation-image-container::before {
         content: '\f00e';
         font-family: 'Font Awesome 5 Free';
         font-weight: 900;
         position: absolute;
         top: 50%;
         left: 50%;
         transform: translate(-50%, -50%);
         width: 50px;
         height: 50px;
         background: rgba(0, 0, 0, 0.6);
         color: #fff;
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         font-size: 20px;
         opacity: 0;
         transition: opacity 0.2s ease;
         pointer-events: none;
         z-index: 10;
     }

     .schedule-post-card .pinterest_card .image-container:hover::before,
     .schedule-post-card .facebook_card .pronunciation-image-container:hover::before {
         opacity: 1;
     }

     /* Video Thumbnail Placeholder Styles */
     .video-thumbnail-placeholder {
         position: relative;
         width: 100%;
         min-height: 300px;
         background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
         display: flex;
         align-items: center;
         justify-content: center;
         overflow: hidden;
     }

     .pinterest_card .video-thumbnail-placeholder {
         border-radius: 20px 20px 0px 0px;
     }

     .facebook_card .pronunciation-image-container.video-thumbnail-placeholder {
         position: relative;
         padding-top: 100%;
         min-height: auto;
     }

     .facebook_card .pronunciation-image-container.video-thumbnail-placeholder .video-placeholder-icon,
     .facebook_card .pronunciation-image-container.video-thumbnail-placeholder .video-play-overlay {
         position: absolute;
         top: 0;
         left: 0;
         width: 100%;
         height: 100%;
     }

     .video-placeholder-icon {
         display: flex;
         align-items: center;
         justify-content: center;
         color: white;
         font-size: 64px;
         opacity: 0.8;
         z-index: 1;
     }

     .video-play-overlay {
         position: absolute;
         top: 0;
         left: 0;
         width: 100%;
         height: 100%;
         background: rgba(0, 0, 0, 0.3);
         display: flex;
         align-items: center;
         justify-content: center;
         transition: background 0.3s ease;
         z-index: 2;
     }

     .video-thumbnail-placeholder:hover .video-play-overlay {
         background: rgba(0, 0, 0, 0.5);
     }

     .video-play-button {
         width: 70px;
         height: 70px;
         background: rgba(255, 255, 255, 0.9);
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         cursor: pointer;
         transition: all 0.3s ease;
         box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
     }

     .video-play-button i {
         color: #333;
         font-size: 24px;
         margin-left: 4px;
     }

     .video-thumbnail-placeholder:hover .video-play-button {
         transform: scale(1.1);
         background: rgba(255, 255, 255, 1);
     }
 </style>
