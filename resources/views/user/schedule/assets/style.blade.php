 <style>
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
 </style>
