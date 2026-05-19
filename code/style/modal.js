// Когда DOM загружен
document.addEventListener('DOMContentLoaded', function() {
    // Все модальные окна
    const modals = document.querySelectorAll('.modal');
    // Все кнопки открытия
    const openButtons = document.querySelectorAll('[data-modal]');
    // Все кнопки закрытия
    const closeButtons = document.querySelectorAll('.close-modal, .btn-cancel');
    // Все формы в модалках
    const forms = document.querySelectorAll('.modal-content form');
    
    // 1. Открытие модального окна
    openButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        });
    });
    
    // 2. Закрытие модального окна
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    });
    
    // 3. Закрытие при клике вне модального окна
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    });
    
    // 4. Закрытие по ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            modals.forEach(modal => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
        }
    });
    
    // 5. Обработка отправки ВСЕХ форм
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmit(this);
        });
    });
});

// Единая функция обработки ВСЕХ форм
async function handleFormSubmit(form) {
    const formData = new FormData(form);
    const modal = form.closest('.modal');
    const field = formData.get('field');
    const newValue = formData.get('new_value');

    const oldValue = formData.get('old_value');
    
    console.log('Отправка формы:', { field, newValue, oldValue });
    
    // Клиентская валидация
    let validationError = validateForm(field, formData);
    if (validationError) {
        showNotification(validationError, 'error');
        return;
    }
    
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        });
        
        console.log('Статус ответа:', response.status);
        
        // Получаем текст ответа для отладки
        const responseText = await response.text();
        console.log('Ответ сервера:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Ошибка парсинга JSON:', e);
            console.error('Ответ был:', responseText);
            showNotification('Ошибка сервера: некорректный ответ', 'error');
            return;
        }
        
        console.log('Распарсенные данные:', data);
        
        if (data.success) {
            // Обновляем интерфейс
            updateUI(field, newValue);
            
            // Закрываем модалку
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Очищаем поля формы пароля
            if (field === 'password') {
                form.reset();
            }
            
            showNotification('Изменения сохранены!', 'success');
        } else {
            showNotification('Ошибка: ' + (data.error || 'Неизвестная ошибка'), 'error');
        }
        
    } catch (error) {
        console.error('Ошибка сети:', error);
        showNotification('Ошибка сети или сервера', 'error');
    }
}

// Функция валидации формы
function validateForm(field, formData) {
    if (field === 'password') {
        const newPassword = formData.get('new_value');
        const confirmPassword = formData.get('confirm_value');
        const currentPassword = formData.get('current_password');
        
        if (!currentPassword) {
            return 'Введите текущий пароль';
        }
        
        if (newPassword.length < 6) {
            return 'Пароль должен быть минимум 6 символов';
        }
        
        if (newPassword !== confirmPassword) {
            return 'Новые пароли не совпадают';
        }
    }
    
    if (field === 'email') {
        const newEmail = formData.get('new_value');
        const confirmEmail = formData.get('confirm_value');
        
        if (!newEmail.includes('@')) {
            return 'Введите корректный email';
        }
        
        if (newEmail !== confirmEmail) {
            return 'Email\'ы не совпадают';
        }
    }
    
    if (field === 'login') {
        const newLogin = formData.get('new_value');
        if (newLogin.length < 3) {
            return 'Логин должен быть минимум 3 символа';
        }
    }
    
    return null; // нет ошибок
}

// Функция обновления интерфейса
function updateUI(field, newValue) {
    switch(field) {
        case 'login':
            const usernameElem = document.getElementById('current-username');
            if (usernameElem) usernameElem.textContent = newValue;
            break;
            
        case 'email':
            const emailElem = document.getElementById('current-email');
            if (emailElem) emailElem.textContent = newValue;
            break;
            
        case 'inform':
            const descElem = document.getElementById('current-description');
            if (descElem) descElem.textContent = newValue;
            break;
            
        // Для пароля ничего не меняем
    }
}

// Функция уведомлений
function showNotification(message, type) {
    // Удаляем старые уведомления
    document.querySelectorAll('.notification').forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 1001;
        animation: slideInRight 0.3s;
    `;
    
    notification.style.background = type === 'success' ? '#4CAF50' : '#f44336';
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// CSS анимации для уведомлений
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);


// Предпросмотр изображения при выборе файла
function setupImagePreview(fileInputId, imageDisplayId) {
    const fileInput = document.getElementById(fileInputId);
    const imageDisplay = document.getElementById(imageDisplayId);
    
    if (!fileInput || !imageDisplay) return;
    
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                // Меняем src у существующего изображения
                imageDisplay.src = e.target.result;
            };
            
            reader.readAsDataURL(file);
        }
    });
}

// Обработка отправки формы изображения
function setupImageForm(formId, imageElementId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const fileInput = this.querySelector('input[type="file"]');
        if (!fileInput.files[0]) {
            showNotification('Выберите файл', 'error');
            return;
        }
        
        const formData = new FormData(this);
        const modal = this.closest('.modal');
        const submitBtn = this.querySelector('.btn-save');
        const originalText = submitBtn.textContent;
        
        // Показываем загрузку
        submitBtn.textContent = 'Загрузка...';
        submitBtn.disabled = true;
        
        try {
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Обновляем изображение на странице настроек
                if (imageElementId === 'avatar-display') {
                    const avatarOnPage = document.querySelector('#avatar-preview');
                    if (avatarOnPage) {
                        avatarOnPage.src = `pic/${data.filename}?t=${Date.now()}`;
                    }
                }
                
                // Закрываем модалку
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                
                // Перезагружаем страницу через 0.5 сек
                setTimeout(() => {
                    location.reload();
                }, 500);
                
                showNotification('Изображение обновлено!', 'success');
            } else {
                showNotification('Ошибка: ' + data.error, 'error');
            }
        } catch (error) {
            console.error('Ошибка:', error);
            showNotification('Ошибка сети', 'error');
        } finally {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    });
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Настраиваем предпросмотр
    setupImagePreview('avatar-file', 'avatar-display');
    setupImagePreview('backimg-file', 'backimg-display');
    
    // Настраиваем формы
    setupImageForm('form-avatar', 'avatar-display');
    setupImageForm('form-backimg', 'backimg-display');
});






