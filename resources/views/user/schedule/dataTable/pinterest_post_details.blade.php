 <div class="pinterest_card">
     <!-- HEADER / TOP BAR -->
     <div class="card-header-custom">
         <!-- Left Icons -->
         <div class="d-flex align-items-center">
             <i class="bi bi-heart header-icon"></i>
             <i class="bi bi-chat header-icon"></i>
             <i class="bi bi-upload header-icon"></i>
             <i class="bi bi-three-dots header-icon"></i>
         </div>

         <!-- Right Buttons -->
         <div class="d-flex align-items-center">
             <div class="dropdown me-2">
                 <button class="btn btn-entertainment">
                     {{ $post->account_name ?? ucfirst($post->social_type) }}
                 </button>
             </div>
         </div>
     </div>

     <!-- IMAGE/MEDIA SECTION -->
     <div class="image-container">
         <!-- Placeholder Image matching the aspect ratio and theme -->
         <img src="{{ $post->image }}" alt="Product post image" class="post-image"
             onerror="this.onerror=null; this.src='{{ no_image() }}';">
     </div>

     <!-- CONTENT SECTION -->
     <div class="card-content">

         <h4 class="title-text">{{ $post->title }}</h4>

         <!-- COMMENTS SECTION -->
         <div class="comment-input-container">
             <p class="mb-3 text-muted">No comments yet</p>
             <div class="d-flex align-items-center">
                 <input type="text" class="form-control comment-input"
                     placeholder="Add a comment to start the conversation">
                 <div class="d-flex ms-3">
                     <i class="bi bi-emoji-smile comment-icon me-3"></i>
                     <i class="bi bi-chat-square-text comment-icon"></i>
                 </div>
             </div>
         </div>
     </div>
 </div>
