document.addEventListener('DOMContentLoaded', function () {

    const navbar = document.querySelector('.navbar-expand-lg');
    const navBtns = document.querySelectorAll('.nav-btn');

    // Debounced scroll event handler
    window.addEventListener('scroll', debounce(function () {
        if (window.scrollY > 2) {
            navbar.classList.add('bg-white');
            navbar.style.transition = "all 0.333s linear";
            navbar.style.borderBottom = "1px solid #0003";
            navbar.style.padding = "0";
            navbar.classList.remove('bg-transparent');
            navBtns.forEach(btn => {
                btn.style.padding = "2px 20px";
                btn.style.transition = "all 0.333s linear";
            });
        } else {
            navbar.classList.remove('bg-white');
            navbar.classList.add('bg-lighter-light');
            navbar.style.padding = "6px 0";
            navbar.style.borderBottom = "none";
            navBtns.forEach(btn => {
                btn.style.padding = "8px 20px";
                btn.style.transition = "all 0.333s linear";
            });
        }
    }, 20));


    const dropdownLinks = document.querySelectorAll('.nav-item.dropdown a');
    const dropdowns = document.querySelectorAll('.nav-show-hide');

    // Initialize all collapsibles
    dropdowns.forEach(dropdown => {
        new bootstrap.Collapse(dropdown, { toggle: false });
    });

    dropdownLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            const targetId = this.getAttribute('data-bs-target');
            const targetDropdown = document.querySelector(targetId);

            // Close all other dropdowns
            dropdowns.forEach(dropdown => {
                if (dropdown !== targetDropdown && dropdown.classList.contains('show')) {
                    const bsCollapse = bootstrap.Collapse.getInstance(dropdown);
                    if (bsCollapse) bsCollapse.hide();
                }
            });

            // Toggle the clicked dropdown
            const bsCollapse = bootstrap.Collapse.getInstance(targetDropdown);
            if (bsCollapse) {
                bsCollapse.toggle();
            } else {
                new bootstrap.Collapse(targetDropdown, { toggle: true });
            }
        });
    });

    // FAQ's Show Hide Functionality
    document.querySelectorAll('.faq-link').forEach(link => {
        link.addEventListener('click', function () {
            const faqItem = this.parentElement;
            const faqContent = faqItem.querySelector('.faq-content');
            const icon = this.querySelector('i');

            // Close any other open FAQ items
            document.querySelectorAll('.faq-item').forEach(item => {
                if (item !== faqItem) {
                    item.classList.remove('active');
                    const content = item.querySelector('.faq-content');
                    const otherIcon = item.querySelector('i');
                    content.style.maxHeight = null;
                    content.style.padding = '0';
                    otherIcon.classList.replace('bx-minus', 'bx-plus');
                }
            });

            // Toggle the clicked FAQ item
            faqItem.classList.toggle('active');
            if (faqItem.classList.contains('active')) {
                faqContent.style.maxHeight = faqContent.scrollHeight + 'px';
                faqContent.style.padding = '10px 0';
                icon.classList.replace('bx-plus', 'bx-minus');
            } else {
                faqContent.style.maxHeight = null;
                faqContent.style.padding = '0';
                icon.classList.replace('bx-minus', 'bx-plus');
            }
        });
    });

    // Plan's Show Hide Functionality
    document.querySelectorAll('.plan-smd-link').forEach(link => {
        link.addEventListener('click', function () {
            const planItem = this.parentElement;
            const planContent = planItem.querySelector('.plan-smd-content');
            const icon = this.querySelector('i');

            // Close any other open Plan items
            document.querySelectorAll('.plan-smd-item').forEach(item => {
                if (item !== planItem) {
                    item.classList.remove('active');
                    const content = item.querySelector('.plan-smd-content');
                    const otherIcon = item.querySelector('i');
                    content.style.maxHeight = null;
                    content.style.padding = '0';
                    otherIcon.classList.replace('bx-minus', 'bx-plus');
                }
            });

            // Toggle the clicked Plan item
            planItem.classList.toggle('active');
            if (planItem.classList.contains('active')) {
                planContent.style.maxHeight = planContent.scrollHeight + 'px';
                planContent.style.padding = '10px 0';
                icon.classList.replace('bx-plus', 'bx-minus');
            } else {
                planContent.style.maxHeight = null;
                planContent.style.padding = '0';
                icon.classList.replace('bx-minus', 'bx-plus');
            }
        });
    });

    // Share Content Show Hide Functionality
    const links = document.querySelectorAll('.share__content__link');
    links.forEach(link => {
        link.addEventListener('click', function () {
            const item = this.closest('.share__content__item');
            const content = item.querySelector('.share__content');
            const isActive = item.classList.contains('active');

            // Close all other items
            document.querySelectorAll('.share__content__item').forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove('active');
                    const otherContent = otherItem.querySelector('.share__content');
                    otherContent.classList.remove('active');
                    otherContent.style.maxHeight = '0';
                }
            });

            // Toggle the clicked item
            if (!isActive) {
                item.classList.add('active');
                content.classList.add('active');
                content.style.maxHeight = content.scrollHeight + 'px';
            } else {
                item.classList.remove('active');
                content.classList.remove('active');
                content.style.maxHeight = '0';
            }
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.navbar-expand-lg')) {
            dropdowns.forEach(dropdown => {
                if (dropdown.classList.contains('show')) {
                    const bsCollapse = bootstrap.Collapse.getInstance(dropdown);
                    if (bsCollapse) bsCollapse.hide();
                }
            });
        }
    });


    // Plan Toggle Functionality
    const monthlyPlans = document.querySelectorAll('.monthly-plan');
    const yearlyPlans = document.querySelectorAll('.yearly-plan');
    const planBtns = document.querySelectorAll('.btn-switch');

    function showPlans(period) {
        if (period === 'monthly') {
            monthlyPlans.forEach(plan => plan.classList.remove('d-none'));
            yearlyPlans.forEach(plan => plan.classList.add('d-none'));
        } else if (period === 'yearly') {
            monthlyPlans.forEach(plan => plan.classList.add('d-none'));
            yearlyPlans.forEach(plan => plan.classList.remove('d-none'));
        }
    }

    planBtns.forEach(btn => {
        // btn.classList.add('switched');
        btn.addEventListener('click', () => {
            const period = btn.getAttribute('data-period');
            showPlans(period);

            // Toggle active class for buttons
            planBtns.forEach(button => button.classList.toggle('switched', button === btn));
        });
    });

    // Initialize with default view
    showPlans('monthly');

    const fixedSection = document.getElementById('fixedSection');
    const fixedLeft = document.getElementById('fixedLeft');
    const fixedRight = document.getElementById('fixedRight');
    // const rect = fixedSection.getBoundingClientRect();

    // Check if the section is in the viewport

    // Debounce function for scroll events
    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Button Toggler For UTM Code
    const toggleButton = document.getElementById('toggleButton');
    const utmCode = document.getElementById('UTM-tooltip');

    // Function to toggle the button's active state
    function toggleColor() {
        toggleButton.classList.toggle('active');
        updateTooltipText(); // Call to update tooltip text
    }

    // Function to display the tooltip
    function displayUTM() {
        utmCode.style.display = "flex";
        utmCode.style.color = "#fff";
        updateTooltipText();
    }

    // Function to hide the tooltip
    function hideUTM() {
        utmCode.style.display = "none";
    }

    // Function to update the tooltip text based on active state
    function updateTooltipText() {
        if (toggleButton.classList.contains('active')) {
            utmCode.textContent = 'UTM Code Activated';
            utmCode.style.backgroundColor = "#2272b0";
            utmCode.style.color = "#fff";
        } else {
            utmCode.textContent = 'UTM Code Deactivated';
            utmCode.style.backgroundColor = "#333";
            utmCode.style.color = "#fff";
        }
    }

    // Event listeners
    toggleButton.addEventListener('click', toggleColor);
    toggleButton.addEventListener('mouseenter', displayUTM);
    toggleButton.addEventListener('mouseleave', hideUTM);

});