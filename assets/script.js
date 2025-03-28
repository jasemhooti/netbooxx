// اسکرول نرم برای لینک‌های داخلی
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// انیمیشن اسکرول برای المان‌ها
const animateOnScroll = () => {
    const elements = document.querySelectorAll('.package-card, .feature, .contact-form');
    
    elements.forEach(element => {
        const elementTop = element.getBoundingClientRect().top;
        const elementBottom = element.getBoundingClientRect().bottom;
        
        if (elementTop < window.innerHeight && elementBottom > 0) {
            element.classList.add('animate');
        }
    });
};

// اجرای انیمیشن در اسکرول
window.addEventListener('scroll', animateOnScroll);
window.addEventListener('load', animateOnScroll);

// فرم تماس
const contactForm = document.querySelector('.contact-form');
if (contactForm) {
    contactForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(contactForm);
        const data = Object.fromEntries(formData.entries());
        
        try {
            const response = await fetch('contact.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('پیام شما با موفقیت ارسال شد.');
                contactForm.reset();
            } else {
                alert('خطا در ارسال پیام. لطفاً دوباره تلاش کنید.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('خطا در ارسال پیام. لطفاً دوباره تلاش کنید.');
        }
    });
}

// منوی موبایل
const createMobileMenu = () => {
    const nav = document.querySelector('nav');
    const navLinks = document.querySelector('.nav-links');
    
    const menuButton = document.createElement('button');
    menuButton.className = 'mobile-menu-button';
    menuButton.innerHTML = '<i class="fas fa-bars"></i>';
    
    nav.insertBefore(menuButton, navLinks);
    
    menuButton.addEventListener('click', () => {
        navLinks.classList.toggle('show');
    });
};

// اجرای منوی موبایل در صفحات کوچک
if (window.innerWidth <= 768) {
    createMobileMenu();
}

// انیمیشن شمارنده برای آمار
const animateCounter = (element, target) => {
    let current = 0;
    const increment = target / 100;
    
    const updateCounter = () => {
        if (current < target) {
            current += increment;
            element.textContent = Math.round(current);
            requestAnimationFrame(updateCounter);
        } else {
            element.textContent = target;
        }
    };
    
    updateCounter();
};

// اجرای انیمیشن شمارنده برای آمار
const stats = document.querySelectorAll('.stat-number');
if (stats.length > 0) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = parseInt(entry.target.getAttribute('data-target'));
                animateCounter(entry.target, target);
                observer.unobserve(entry.target);
            }
        });
    });
    
    stats.forEach(stat => observer.observe(stat));
}

// اعتبارسنجی فرم
const validateForm = (form) => {
    const inputs = form.querySelectorAll('input[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('error');
        } else {
            input.classList.remove('error');
        }
    });
    
    return isValid;
};

// اضافه کردن اعتبارسنجی به فرم‌ها
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', (e) => {
        if (!validateForm(form)) {
            e.preventDefault();
            alert('لطفاً تمام فیلدهای الزامی را پر کنید.');
        }
    });
}); 