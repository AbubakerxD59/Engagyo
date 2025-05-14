 <div class="d-flex justify-content-center">
     <div class="col-md-6 m-1 d-flex justify-content-end">
         <button class="btn btn-outline-info btn-sm fix-post" data-post-id="{{ $post->id }}" title="Fix this Post!">
             <i class="fa fa-tools"></i>
         </button>
     </div>

 </div>
 <div class="d-flex justify-content-center">

     <a href="{{ $post->url }}" target="_blank" class="col-md-6">
         <div class="card">
             <div class="card-header with-border clearfix d-flex justify-content-center">
                 <div class="card-title">
                     <img src="{{ $post->image }}" alt="{{ no_image() }}" width="150px">
                 </div>
             </div>
             <div class="card-body p-2">
                 <div>
                     <div class="form-group font-weight-bold">
                         <p>{{ $post->title }}</p>
                     </div>
                     <div class="form-group font-weight-bold">
                         <span class="row">
                             <i class="fa fa-clock m-1"></i>
                             <p>{{ date('Y-m-d H:i A', strtotime($post->publish_date)) }}</p>
                         </span>
                     </div>
                 </div>
             </div>
         </div>
     </a>
 </div>
 @if ($post->status == -1)
     <div class="d-flex justify-content-center">
         <div class="col-md-6 p-3 bg-danger rounded text-center">
             <span>
                 <i class="fa fa-exclamation"></i>
             </span>
             <br>
             <span>
                 {{ $post->message }}
             </span>
         </div>
     </div>
 @endif
