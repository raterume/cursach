// Когда DOM загружен
document.addEventListener('DOMContentLoaded', function() {
    // ВСЯ ОБРАБОТКА МОДАЛОК ЗДЕСЬ
    
    // 1. Все модальные окна
    const modals = document.querySelectorAll('.modal');
    // Все кнопки открытия
    const openButtons = document.querySelectorAll('[data-modal]');
    // Все кнопки закрытия
    const closeButtons = document.querySelectorAll('.close-modal, .btn-cancel');
    // Все формы в модалках
    const forms = document.querySelectorAll('.modal-content form');
    
    // 1.1. Открытие модального окна
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
    
    // 1.2. Закрытие модального окна
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    });
    
    // 1.3. Закрытие при клике вне модального окна
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    });
    
    // 1.4. Закрытие по ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            modals.forEach(modal => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
        }
    });
    
    // 1.5. Обработка отправки ВСЕХ форм
    forms.forEach(form => {
        // ИСКЛЮЧАЕМ форму создания поста - у нее своя обработка
        if (form.id !== 'form-create-post' && form.id !== 'form-edit-post') {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('Отправка формы:', this.id);
            });
        }
    });
    
    // 2. ИНИЦИАЛИЗАЦИЯ МОДАЛКИ СОЗДАНИЯ ПОСТА
    console.log('DOM загружен, инициализация модалки создания поста...');
    setupCreatePostModal();
    
    // 3. Обработка кнопки создания поста
    const createBtn = document.querySelector('.btn-create');
    if (createBtn) {
        createBtn.addEventListener('click', function() {
            console.log('Клик по кнопке "Создать"');
            const modal = document.getElementById('modal-create-post');
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
                
                // Фокус на текстовое поле
                const textarea = modal.querySelector('#post-text');
                if (textarea) {
                    setTimeout(() => textarea.focus(), 100);
                }
            }
        });
    }
});




// Настройка модалки редактирования поста
function setupEditPostModal() {
    const modal = document.getElementById('modal-edit-post');
    const form = document.getElementById('form-edit-post');
    const fileInput = document.getElementById('edit-post-images');
    const previewsContainer = document.getElementById('edit-image-previews');
    const existingContainer = document.getElementById('existing-images');
    const textarea = document.getElementById('edit-post-text');
    const charCount = document.getElementById('edit-char-count');
    const postIdInput = document.getElementById('edit-post-id');
    
    console.log('Инициализация модалки редактирования:', {
        modal: !!modal,
        form: !!form,
        fileInput: !!fileInput
    });
    
    if (!modal || !form) {
        console.error('Не найдены элементы модалки редактирования');
        return;
    }
    
    let selectedFiles = []; // Новые файлы
    let existingImages = []; // Существующие изображения
    
    // 1. Обработка кликов по кнопкам редактирования
    document.addEventListener('click', function(e) {
        const editBtn = e.target.closest('.edit-post-btn');
        if (editBtn) {
            e.preventDefault();
            e.stopPropagation();
            
            const postId = editBtn.dataset.postId;
            const postText = editBtn.dataset.postText || '';
            const postImages = editBtn.dataset.postImages || '';
            
            console.log('Редактирование поста:', { 
                postId, 
                postText: postText.substring(0, 50),
                postImages 
            });
            
            // Заполняем форму
            postIdInput.value = postId;
            textarea.value = postText;
            
            // Счетчик символов
            if (charCount) {
                charCount.textContent = postText.length;
                charCount.className = '';
                if (postText.length > 900) charCount.classList.add('warning');
                if (postText.length > 980) charCount.classList.add('error');
            }
            
            // Обрабатываем изображения
            selectedFiles = [];
            existingImages = postImages ? postImages.split('||').filter(img => img.trim()) : [];
            
            console.log('Существующие изображения:', existingImages);
            
            // Показываем существующие изображения
            renderExistingImages();
            
            // Обновляем превью новых файлов
            updatePreviews();
            
            // Открываем модалку
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            // Фокус на текстовое поле
            setTimeout(() => {
                if (textarea) textarea.focus();
            }, 100);
        }
    });
    
    // 2. Рендер существующих изображений
    function renderExistingImages() {
        if (!existingContainer) return;
        
        existingContainer.innerHTML = '';
        
        existingImages.forEach((imageName, index) => {
            const imageItem = document.createElement('div');
            imageItem.className = 'existing-image-item';
            imageItem.dataset.index = index;
            
            imageItem.innerHTML = `
                <div class="existing-image-wrapper">
                    <img src="pic/${imageName}" class="existing-image-preview" alt="Изображение">
                    <div class="existing-image-controls">
                        <button type="button" class="btn-remove-existing" data-index="${index}">×</button>
                        <input type="hidden" name="keep_images[]" value="${imageName}">
                    </div>
                </div>
            `;
            
            existingContainer.appendChild(imageItem);
            
            // Обработка удаления существующего изображения
            const removeBtn = imageItem.querySelector('.btn-remove-existing');
            removeBtn.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                console.log('Удаление существующего изображения:', index);
                removeExistingImage(index);
            });
        });
    }
    
    // 3. Удаление существующего изображения
    function removeExistingImage(index) {
        existingImages.splice(index, 1);
        renderExistingImages();
    }
    
    // 4. Функция добавления превью
    function addImagePreview(file, index) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const previewItem = document.createElement('div');
            previewItem.className = 'image-preview-item';
            previewItem.dataset.index = index;
            
            previewItem.innerHTML = `
                <img src="${e.target.result}" class="preview-image" alt="Превью">
                <button type="button" class="remove-image" data-index="${index}">×</button>
            `;
            
            // Вставляем перед кнопкой добавления, если она есть
            const uploadBtn = previewsContainer.querySelector('.image-preview-item.empty');
            if (uploadBtn) {
                previewsContainer.insertBefore(previewItem, uploadBtn);
            } else {
                previewsContainer.appendChild(previewItem);
            }
            
            // Обработка удаления
            const removeBtn = previewItem.querySelector('.remove-image');
            removeBtn.addEventListener('click', function() {
                console.log('Удаление нового изображения:', index);
                removeNewImage(index);
            });
        };
        
        reader.readAsDataURL(file);
    }
    
    // 5. Удаление нового изображения
    function removeNewImage(index) {
        selectedFiles.splice(index, 1);
        updatePreviews();
    }
    
    // 6. Обновление всех превью
    function updatePreviews() {
        if (!previewsContainer) return;
        
        // Удаляем все текущие превью новых файлов
        const existingPreviews = previewsContainer.querySelectorAll('.image-preview-item:not(.empty)');
        existingPreviews.forEach(preview => preview.remove());
        
        // Добавляем превью для каждого файла
        selectedFiles.forEach((file, index) => {
            addImagePreview(file, index);
        });
        
        // Управляем кнопкой загрузки
        manageUploadButton();
    }
    
    // 7. Управление кнопкой загрузки
    function manageUploadButton() {
        if (!previewsContainer) return;
        
        // Удаляем все существующие кнопки загрузки
        const existingUploadBtns = previewsContainer.querySelectorAll('.image-preview-item.empty');
        existingUploadBtns.forEach(btn => btn.remove());
        
        // Добавляем новую кнопку только если нужно
        const totalImages = existingImages.length + selectedFiles.length;
        if (totalImages < 4) {
            addUploadButton();
        }
    }
    
    // 8. Добавление кнопки загрузки
    function addUploadButton() {
        const uploadBtn = document.createElement('div');
        uploadBtn.className = 'image-preview-item empty';
        uploadBtn.innerHTML = `
            <label for="edit-post-images" class="upload-label">
                <div class="upload-placeholder">
                    <span>+</span>
                    <small>Добавить фото</small>
                </div>
            </label>
        `;
        
        previewsContainer.appendChild(uploadBtn);
        
        // При клике на кнопку - кликаем по скрытому input
        uploadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (fileInput) fileInput.click();
        });
    }
    
    // 9. Обработка выбора файлов
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const newFiles = Array.from(this.files);
            
            // Проверяем общее количество
            const totalImages = existingImages.length + selectedFiles.length + newFiles.length;
            if (totalImages > 4) {
                showNotification(`Можно загрузить не более 4 изображений`, 'error');
                this.value = '';
                return;
            }
            
            // Проверяем каждый файл
            newFiles.forEach(file => {
                if (file.type.startsWith('image/')) {
                    selectedFiles.push(file);
                }
            });
            
            // Обновляем превью
            updatePreviews();
            
            // Сбрасываем input
            this.value = '';
        });
    }
    
// 10. Обработка отправки формы - УПРОЩЕННАЯ ВЕРСИЯ
form.addEventListener('submit', async function(e) {
    e.preventDefault();
    console.log('Отправка формы редактирования поста');
    
    const text = textarea.value.trim();
    const postId = postIdInput.value;
    
    // ОТЛАДКА
    console.log('=== ОТЛАДКА ===');
    console.log('Текст:', text);
    console.log('Post ID:', postId);
    console.log('Существующие изображения:', existingImages);
    console.log('Выбранные файлы:', selectedFiles);
    
    if (!text && existingImages.length === 0 && selectedFiles.length === 0) {
        showNotification('Пост должен содержать текст или изображения', 'error');
        return;
    }
    
    // Вариант 1: Использовать существующий input файлов
    // Сначала очистим его
    if (fileInput) {
        fileInput.value = '';
        
        // Создаем DataTransfer для добавления файлов
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => {
            dataTransfer.items.add(file);
        });
        
        // Присваиваем файлы input
        fileInput.files = dataTransfer.files;
        console.log('Файлы в input:', fileInput.files.length);
    }
    
    // Вариант 2: Использовать FormData из формы (включая обновленный input)
    const formData = new FormData(this);
    
    // Добавляем существующие изображения
    existingImages.forEach(img => {
        formData.append('keep_images[]', img);
    });
    
    // ОТЛАДКА FormData
    console.log('=== FormData содержимое ===');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ':', pair[1]);
    }
    
    const submitBtn = this.querySelector('.btn-save');
    const originalText = submitBtn.textContent;
    
    submitBtn.textContent = 'Сохранение...';
    submitBtn.disabled = true;
    
    try {
        console.log('Отправка на:', this.action);
        
        const response = await fetch(this.action, {
            method: 'POST',
            body: formData
        });
        
        
        const responseText = await response.text();
        console.log('Ответ сервера (текст):', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
            console.log('Ответ сервера (JSON):', data);
        } catch (jsonError) {
            console.error('Ошибка парсинга JSON:', jsonError);
            throw new Error('Некорректный ответ сервера');
        }
        
        if (data.success) {
            // УСПЕХ
            console.log('Пост обновлен:', data);
            
            // Закрываем модалку
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Очищаем форму
            textarea.value = '';
            selectedFiles = [];
            existingImages = [];
            updatePreviews();
            if (charCount) {
                charCount.textContent = '0';
                charCount.className = '';
            }
            
            showNotification('Пост обновлен!', 'success');
            
            // Перезагружаем страницу
            setTimeout(() => {
                location.reload();
            }, 1000);
            
        } else {
            showNotification('Ошибка: ' + (data.error || 'Неизвестная ошибка'), 'error');
        }
        
    } catch (error) {
        console.error('Ошибка:', error);
        showNotification('Ошибка сети: ' + error.message, 'error');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
});
    
    // 11. Инициализация начального состояния
    console.log('Инициализация начального состояния редактирования');
    if (previewsContainer) {
        // Удаляем все что есть в контейнере
        previewsContainer.innerHTML = '';
        
        // Добавляем кнопку загрузки
        addUploadButton();
    }
    
    // 12. Закрытие модалки
    const closeBtn = modal.querySelector('.close-modal');
    const cancelBtn = modal.querySelector('.btn-cancel');
    
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
    }
    
    // Закрытие при клике вне модалки
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM загружен, инициализация модалки редактирования...');
    setupEditPostModal();
});

// Функция уведомлений (если еще нет)
function showNotification(message, type) {
    console.log('Уведомление:', type, message);
    
    // Удаляем старые уведомления
    document.querySelectorAll('.notification').forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    // Стили
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 6px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        background: ${type === 'success' ? '#4CAF50' : '#f44336'};
    `;
    
    document.body.appendChild(notification);
    
    // CSS анимация
    const style = document.createElement('style');
    if (!document.querySelector('#notification-animations')) {
        style.id = 'notification-animations';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
    
    notification.style.animation = 'slideIn 0.3s ease-out';
    
    // Автоудаление через 3 секунды
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}


