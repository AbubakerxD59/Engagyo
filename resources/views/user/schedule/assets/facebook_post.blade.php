<style>
    /* facebook post */
    .facebook_card {
        max-width: 550px;
        /* Standard Pinterest/Social Card width */
        width: 100%;
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        justify-self: center;
    }

    .facebook_card .post-header-text {
        line-height: 1.2;
    }

    .facebook_card .post-author {
        font-weight: 600;
        color: #050505;
    }

    .facebook_card .post-date {
        font-size: 0.85rem;
        color: #606770;
    }

    /* Content Grid Styling */
    .facebook_card .content-block {
        padding: 0;
        border: 1px solid #e5e5e5;
        /* Subtle borders between blocks */
    }

    .facebook_card .content-block-inner {
        background-color: white;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .facebook_card .pronunciation-image-container {
        width: 100%;
        padding-top: 100%;
        /* 1:1 Aspect Ratio for top images */
        position: relative;
        overflow: hidden;
    }

    .facebook_card .pronunciation-image-container img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .facebook_card .word-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 10px 0;
    }

    /* Pronunciation Marks and Text */
    .facebook_card .pronunciation-pair {
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 15px 10px;
        border-top: 1px solid #e5e5e5;
    }

    .facebook_card .pronunciation-item {
        display: flex;
        align-items: center;
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0 5px;
    }

    .facebook_card .mark-icon {
        font-size: 1.2rem;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-right: 8px;
        color: white;
    }

    .facebook_card .mark-incorrect {
        background-color: #fa383e;
        /* Red color */
    }

    .facebook_card .mark-correct {
        background-color: #4CAF50;
        /* Green color */
    }

    .facebook_card .incorrect-text {
        color: #fa383e;
    }

    .facebook_card .correct-text {
        color: #4CAF50;
    }

    .facebook_card .badge-kid {
        background-color: #f0f2f5;
        color: #606770;
        padding: 2px 5px;
        font-size: 0.65rem;
        border-radius: 3px;
        margin-left: 5px;
        font-weight: 400;
        text-transform: uppercase;
    }

    /* Bottom "Shared From" Section */
    .facebook_card .shared-source {
        padding: 10px 15px;
        border-top: 1px solid #e5e5e5;
    }

    .facebook_card .source-caption {
        font-size: 0.9rem;
        color: #050505;
        font-weight: 500;
    }

    .facebook_card .source-author {
        font-size: 0.85rem;
        color: #1877f2;
        /* Facebook Blue */
        font-weight: 600;
    }

    /* Action Bar */
    .facebook_card .post-actions {
        border-top: 1px solid #e5e5e5;
        padding-top: 5px;
    }

    .facebook_card .action-btn {
        color: #606770;
        font-weight: 600;
        cursor: pointer;
    }

    .facebook_card .action-btn:hover {
        background-color: #f0f2f5;
        border-radius: 6px;
    }

    .facebook_card .social_profile {
        height: 35px !important;
        width: 35px !important;
    }

    /* Video Thumbnail Placeholder Styles for Facebook */
    .facebook_card .pronunciation-image-container.video-thumbnail-placeholder {
        position: relative;
        padding-top: 100%;
        min-height: auto;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .facebook_card .pronunciation-image-container.video-thumbnail-placeholder .video-placeholder-icon,
    .facebook_card .pronunciation-image-container.video-thumbnail-placeholder .video-play-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }

    .facebook_card .video-placeholder-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 64px;
        opacity: 0.8;
        z-index: 1;
    }

    .facebook_card .video-play-overlay {
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

    .facebook_card .video-thumbnail-placeholder:hover .video-play-overlay {
        background: rgba(0, 0, 0, 0.5);
    }

    .facebook_card .video-play-button {
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

    .facebook_card .video-play-button i {
        color: #333;
        font-size: 24px;
        margin-left: 4px;
    }

    .facebook_card .video-thumbnail-placeholder:hover .video-play-button {
        transform: scale(1.1);
        background: rgba(255, 255, 255, 1);
    }

    /* facebook post */
</style>
