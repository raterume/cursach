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
        // ИСКЛЮЧАЕМ формы, у которых своя обработка
    if (form.id !== 'form-create-post' && 
        form.id !== 'form-edit-post' && 
        form.id !== 'form-delete-post' &&
        form.id !== 'form-edit-comment' &&
        form.id !== 'form-delete-comment') {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Отправка формы:', this.id);
            // handleFormSubmit(this); // УДАЛИТЕ ЭТУ СТРОКУ
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

// Ограничение на количество изображений
const MAX_IMAGES = 4;

// Глобальная переменная для отслеживания инициализации
let isCreatePostModalInitialized = false;

// Функция для создания поста
function setupCreatePostModal() {
    // Защита от повторной инициализации
    if (isCreatePostModalInitialized) {
        console.log('Модалка уже инициализирована, пропускаем');
        return;
    }
    
    const modal = document.getElementById('modal-create-post');
    const form = document.getElementById('form-create-post');
    const fileInput = document.getElementById('post-images');
    const previewsContainer = document.getElementById('image-previews');
    const textarea = document.getElementById('post-text');
    const charCount = document.getElementById('char-count');
    
    console.log('Инициализация модалки создания поста:', {
        modal: !!modal,
        form: !!form,
        fileInput: !!fileInput,
        previewsContainer: !!previewsContainer,
        textarea: !!textarea,
        charCount: !!charCount
    });
    
    if (!modal || !form) {
        console.error('Не найдены основные элементы модалки');
        return;
    }
    
    // Отмечаем что инициализация началась
    isCreatePostModalInitialized = true;
    
    // Массив для хранения выбранных файлов
    let selectedFiles = [];
    
    // 1. ИНИЦИАЛИЗАЦИЯ СЧЕТЧИКА СИМВОЛОВ
    if (textarea && charCount) {
        console.log('Настройка счетчика символов');
        
        // Функция обновления счетчика
        const updateCharCount = function() {
            const length = this.value.length;
            charCount.textContent = length;
            
            // Изменяем цвет при приближении к лимиту
            charCount.className = '';
            if (length > 900) {
                charCount.classList.add('warning');
            }
            if (length > 980) {
                charCount.classList.add('error');
            }
            
            console.log('Символов:', length);
        };
        
        // Вешаем обработчик
        textarea.addEventListener('input', updateCharCount);
        
        // Инициализируем начальное значение
        charCount.textContent = textarea.value.length;
    }
    
    // 2. ФУНКЦИЯ ДОБАВЛЕНИЯ ПРЕВЬЮ КАРТИНКИ
    function addImagePreview(file, index) {
        console.log('Добавление превью:', index, file.name);
        
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
                console.log('Удаление изображения:', index);
                removeImage(index);
            });
        };
        
        reader.onerror = function() {
            console.error('Ошибка чтения файла:', file.name);
        };
        
        reader.readAsDataURL(file);
    }
    
    // 3. ФУНКЦИЯ УДАЛЕНИЯ ИЗОБРАЖЕНИЯ
    function removeImage(index) {
        console.log('Удаляем файл с индексом:', index);
        selectedFiles.splice(index, 1);
        updatePreviews();
    }
    
    // 4. ФУНКЦИЯ ОБНОВЛЕНИЯ ВСЕХ ПРЕВЬЮ
    function updatePreviews() {
        console.log('Обновление превью. Файлов:', selectedFiles.length);
        
        // Удаляем все текущие превью (кроме кнопки загрузки)
        const existingPreviews = previewsContainer.querySelectorAll('.image-preview-item:not(.empty)');
        existingPreviews.forEach(preview => preview.remove());
        
        // Добавляем превью для каждого файла с новыми индексами
        selectedFiles.forEach((file, index) => {
            addImagePreview(file, index);
        });
        
        // Управляем кнопкой загрузки
        manageUploadButton();
    }
    
    // 5. УПРАВЛЕНИЕ КНОПКОЙ ЗАГРУЗКИ
    function manageUploadButton() {
        // Удаляем все существующие кнопки загрузки
        const existingUploadBtns = previewsContainer.querySelectorAll('.image-preview-item.empty');
        existingUploadBtns.forEach(btn => btn.remove());
        
        // Добавляем новую кнопку только если нужно
        if (selectedFiles.length < MAX_IMAGES) {
            addUploadButton();
        }
    }
    
    // 6. ФУНКЦИЯ ДОБАВЛЕНИЯ КНОПКИ ЗАГРУЗКИ
    function addUploadButton() {
        console.log('Добавление кнопки загрузки');
        
        const uploadBtn = document.createElement('div');
        uploadBtn.className = 'image-preview-item empty';
        uploadBtn.innerHTML = `
            <label for="post-images" class="upload-label">
                <div class="upload-placeholder">
                    <span>+</span>
                    <small>Добавить фото</small>
                </div>
            </label>
        `;
        
        previewsContainer.appendChild(uploadBtn);
        
        // При клике на кнопку - кликаем по скрытому input
        uploadBtn.addEventListener('click', function(e) {
            console.log('Клик по кнопке загрузки');
            e.preventDefault();
            fileInput.click();
        });
    }
    
    // 7. ОБРАБОТКА ВЫБОРА ФАЙЛОВ
    if (fileInput) {
        // Удаляем старые обработчики если есть
        fileInput.removeEventListener('change', handleFileSelect);
        
        function handleFileSelect(e) {
            console.log('Выбраны файлы:', this.files.length);
            
            const newFiles = Array.from(this.files);
            
            // Проверяем общее количество
            if (selectedFiles.length + newFiles.length > MAX_IMAGES) {
                showNotification(`Можно загрузить не более ${MAX_IMAGES} изображений`, 'error');
                this.value = '';
                return;
            }
            
            // Проверяем каждый файл
            newFiles.forEach(file => {
                if (file.type.startsWith('image/')) {
                    selectedFiles.push(file);
                    console.log('Добавлен файл:', file.name, file.size);
                } else {
                    console.warn('Пропущен не-изображение:', file.name);
                }
            });
            
            // Обновляем превью
            updatePreviews();
            
            // Сбрасываем input
            this.value = '';
        }
        
        fileInput.addEventListener('change', handleFileSelect);
    }
    
    // 8. ОБРАБОТКА ОТПРАВКИ ФОРМЫ
    // Удаляем старый обработчик если есть
    form.removeEventListener('submit', handleFormSubmit);
    
    async function handleFormSubmit(e) {
        e.preventDefault();
        console.log('Отправка формы создания поста');
        
        const text = textarea.value.trim();
        
        // Валидация
        if (!text && selectedFiles.length === 0) {
            showNotification('Добавьте текст или изображение', 'error');
            return;
        }
        
        console.log('Данные для отправки:', {
            text: text,
            files: selectedFiles.length
        });
        
        const formData = new FormData();
        
        // Добавляем текст
        if (text) {
            formData.append('text', text);
        }
        
        // Добавляем файлы
        selectedFiles.forEach((file, index) => {
            formData.append('images[]', file, file.name || `image_${index}`);
        });
        
        const submitBtn = this.querySelector('.btn-save');
        const originalText = submitBtn.textContent;
        
        // Показываем загрузку
        submitBtn.textContent = 'Публикация...';
        submitBtn.disabled = true;
        
        try {
            console.log('Отправка запроса на:', this.action);
            
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData
            });
            
            console.log('Статус ответа:', response.status);
            
            // Получаем текст ответа
            const responseText = await response.text();
            console.log('Ответ сервера (текст):', responseText);
            
            // Парсим JSON
            let data;
            try {
                data = JSON.parse(responseText);
                console.log('Ответ сервера (JSON):', data);
            } catch (jsonError) {
                console.error('Ошибка парсинга JSON:', jsonError);
                console.error('Сырой ответ:', responseText);
                throw new Error('Сервер вернул некорректный ответ');
            }
            
            if (data.success) {
                // УСПЕХ
                console.log('Пост создан успешно:', data);
                
                // Закрываем модалку
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                
                // Очищаем форму
                textarea.value = '';
                selectedFiles = [];
                updatePreviews();
                if (charCount) {
                    charCount.textContent = '0';
                    charCount.className = '';
                }
                
                showNotification('Пост опубликован!', 'success');
                
                // Перезагружаем страницу через 1 секунду
                setTimeout(() => {
                    console.log('Перезагрузка страницы...');
                    location.reload();
                }, 1000);
                
            } else {
                // ОШИБКА СЕРВЕРА
                console.error('Ошибка сервера:', data.error);
                showNotification('Ошибка: ' + (data.error || 'Неизвестная ошибка'), 'error');
            }
            
        } catch (error) {
            // ОШИБКА СЕТИ ИЛИ СЕРВЕРА
            console.error('Ошибка при отправке:', error);
            showNotification('Ошибка сети: ' + error.message, 'error');
        } finally {
            // Восстанавливаем кнопку
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    }
    
    form.addEventListener('submit', handleFormSubmit);
    
    // 9. ИНИЦИАЛИЗИРУЕМ НАЧАЛЬНОЕ СОСТОЯНИЕ
    console.log('Инициализация начального состояния');
    if (previewsContainer) {
        // Удаляем все что есть в контейнере
        previewsContainer.innerHTML = '';
        
        // Добавляем кнопку загрузки
        manageUploadButton();
    }
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM загружен, инициализация модалки создания поста...');
    
    // Инициализируем модалку
    setupCreatePostModal();
    
    // Обработка кнопки создания поста
    const createBtn = document.querySelector('.btn-create');
    if (createBtn) {
        // Удаляем старый обработчик если есть
        createBtn.removeEventListener('click', handleCreateClick);
        
        function handleCreateClick() {
            console.log('Клик по кнопке "Создать"');
            const modal = document.getElementById('modal-create-post');
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
                
                // Сбрасываем состояние при каждом открытии
                const textarea = modal.querySelector('#post-text');
                const charCount = modal.querySelector('#char-count');
                const previewsContainer = modal.querySelector('#image-previews');
                
                if (textarea) {
                    textarea.value = '';
                    setTimeout(() => textarea.focus(), 100);
                }
                
                if (charCount) {
                    charCount.textContent = '0';
                    charCount.className = '';
                }
                
                if (previewsContainer) {
                    // Удаляем все превью
                    const previews = previewsContainer.querySelectorAll('.image-preview-item:not(.empty)');
                    previews.forEach(preview => preview.remove());
                    
                    // Оставляем только одну кнопку загрузки
                    const uploadBtns = previewsContainer.querySelectorAll('.image-preview-item.empty');
                    if (uploadBtns.length > 1) {
                        // Оставляем только первую
                        for (let i = 1; i < uploadBtns.length; i++) {
                            uploadBtns[i].remove();
                        }
                    }
                }
            }
        }
        
        createBtn.addEventListener('click', handleCreateClick);
    }
});

// Функция уведомлений
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
    
    // Анимация появления
    notification.style.animation = 'slideIn 0.3s ease-out';
    
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
    
    // Автоудаление через 3 секунды
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}