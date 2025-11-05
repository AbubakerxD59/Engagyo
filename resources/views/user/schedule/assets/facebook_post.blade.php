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

    .social-post-card .post-header-text {
        line-height: 1.2;
    }

    .social-post-card .post-author {
        font-weight: 600;
        color: #050505;
    }

    .social-post-card .post-date {
        font-size: 0.85rem;
        color: #606770;
    }

    /* Content Grid Styling */
    .social-post-card .content-block {
        padding: 0;
        border: 1px solid #e5e5e5;
        /* Subtle borders between blocks */
    }

    .social-post-card .content-block-inner {
        background-color: white;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .social-post-card .pronunciation-image-container {
        width: 100%;
        padding-top: 100%;
        /* 1:1 Aspect Ratio for top images */
        position: relative;
        overflow: hidden;
    }

    .social-post-card .pronunciation-image-container img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .social-post-card .word-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 10px 0;
    }

    /* Pronunciation Marks and Text */
    .social-post-card .pronunciation-pair {
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 15px 10px;
        border-top: 1px solid #e5e5e5;
    }

    .social-post-card .pronunciation-item {
        display: flex;
        align-items: center;
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0 5px;
    }

    .social-post-card .mark-icon {
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

    .social-post-card .mark-incorrect {
        background-color: #fa383e;
        /* Red color */
    }

    .social-post-card .mark-correct {
        background-color: #4CAF50;
        /* Green color */
    }

    .social-post-card .incorrect-text {
        color: #fa383e;
    }

    .social-post-card .correct-text {
        color: #4CAF50;
    }

    .social-post-card .badge-kid {
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
    .social-post-card .shared-source {
        padding: 10px 15px;
        border-top: 1px solid #e5e5e5;
    }

    .social-post-card .source-caption {
        font-size: 0.9rem;
        color: #050505;
        font-weight: 500;
    }

    .social-post-card .source-author {
        font-size: 0.85rem;
        color: #1877f2;
        /* Facebook Blue */
        font-weight: 600;
    }

    /* Action Bar */
    .social-post-card .post-actions {
        border-top: 1px solid #e5e5e5;
        padding-top: 5px;
    }

    .social-post-card .action-btn {
        color: #606770;
        font-weight: 600;
        cursor: pointer;
    }

    .social-post-card .action-btn:hover {
        background-color: #f0f2f5;
        border-radius: 6px;
    }

    /* facebook post */
</style>
