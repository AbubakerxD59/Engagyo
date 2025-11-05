<style>
    .pinterest_card {
        max-width: 550px;
        /* Standard Pinterest/Social Card width */
        width: 100%;
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        justify-self: center;
    }

    /* --- Top Header Styles --- */
    .pinterest_card .card-header-custom {
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: none;
    }

    .pinterest_card .header-icon {
        font-size: 1.25rem;
        color: var(--icon-color);
        margin-right: 15px;
        cursor: pointer;
        padding: 5px;
        border-radius: 50%;
        transition: background-color 0.2s;
    }

    .pinterest_card .header-icon:hover {
        background-color: var(--subtle-gray);
    }

    /* Specific Heart/Like icon style to match the image's hollow look */
    .pinterest_card .bi-heart-fill {
        color: var(--icon-color);
        /* Overriding Bootstrap fill for the heart */
    }

    /* Dropdown/Save Area */
    .pinterest_card .btn-save {
        background-color: var(--primary-red);
        border-color: var(--primary-red);
        font-weight: 700;
        padding: 8px 16px;
        border-radius: 25px;
        transition: filter 0.2s;
    }

    .pinterest_card .btn-save:hover {
        background-color: #a00017;
        border-color: #a00017;
    }

    .pinterest_card .btn-entertainment {
        background-color: var(--subtle-gray);
        border-color: var(--subtle-gray);
        color: var(--icon-color);
        font-weight: 500;
        border-radius: 25px;
        margin-right: 10px;
    }

    .pinterest_card .btn-entertainment:hover {
        background-color: #ddd;
        border-color: #ddd;
        color: var(--icon-color);
    }


    /* --- Image Area Styles --- */
    .pinterest_card .image-container {
        position: relative;
        background-color: #f8f8f8;
        /* Light background for the image area */
        border-radius: 0 0 0 0;
        /* Keep top corners sharp for header */
        overflow: hidden;
    }

    .pinterest_card .post-image {
        width: 100%;
        height: auto;
        display: block;
        border-radius: 0;
    }

    .pinterest_card .image-overlay-buttons {
        position: absolute;
        bottom: 20px;
        right: 20px;
        z-index: 10;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .pinterest_card .overlay-btn {
        background-color: white;
        color: var(--icon-color);
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        cursor: pointer;
        border: none;
        transition: background-color 0.2s;
    }

    .pinterest_card .overlay-btn:hover {
        background-color: var(--subtle-gray);
    }

    /* --- Content & Comment Area Styles --- */
    .pinterest_card .card-content {
        padding: 20px;
    }

    .pinterest_card .title-text {
        font-weight: 700;
        font-size: 1.5rem;
        margin-bottom: 5px;
    }

    .pinterest_card .author-text {
        color: #6c757d;
        font-size: 1rem;
        margin-bottom: 15px;
    }

    .pinterest_card .favorites-link {
        color: var(--icon-color);
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        margin-bottom: 25px;
    }

    /* Input field styling */
    .pinterest_card .comment-input-container {
        border-top: 1px solid #ddd;
        padding-top: 20px;
    }

    .pinterest_card .comment-input {
        border-radius: 25px;
        padding-left: 15px;
        padding-right: 5px;
        /* Give room for icons */
        height: 50px;
        font-size: 1rem;
        background-color: #f7f7f7;
        border: none;
    }

    .pinterest_card .comment-input:focus {
        background-color: white;
        box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.1);
    }

    .pinterest_card .comment-icon {
        font-size: 1.5rem;
        color: #6c757d;
        cursor: pointer;
        transition: color 0.2s;
    }

    .pinterest_card .comment-icon:hover {
        color: var(--icon-color);
    }
</style>
