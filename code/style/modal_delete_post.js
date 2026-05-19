// Обработка удаления поста
let deleteFormInitialized = false; // Флаг инициализации

function setupDeletePostModal() {
    // Если уже инициализирована - выходим
    if (deleteFormInitialized) {
        console.log('Модалка удаления уже инициализирована');
        return;
    }
    
    const modal = document.getElementById('modal-delete-post');
    const form = document.getElementById('form-delete-post');
    const postIdInput = document.getElementById('delete-post-id');
    
    if (!modal || !form) {
        console.error('Не найдены элементы модалки удаления');
        return;
    }
    
    console.log('Инициализация модалки удаления...');
    deleteFormInitialized = true;
    
    // Один обработчик для ВСЕХ кнопок удаления
    document.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.delete-post-btn, .drop-btn');
        if (deleteBtn) {
            e.preventDefault();
            e.stopPropagation();
            
            const postId = deleteBtn.dataset.postId;
            console.log('Удаление поста ID:', postId);
            
            if (!postId || postId <= 0) {
                showNotification('Неверный ID поста', 'error');
                return;
            }
            
            // Заполняем форму
            postIdInput.value = postId;
            
            // Открываем модалку
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    });
    
    // Флаг для предотвращения повторной отправки
    let isSubmitting = false;
    
    // Обработчик отправки формы (ОДИН раз!)
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Если уже отправляем - игнорируем
        if (isSubmitting) {
            console.log('Форма уже отправляется...');
            return;
        }
        
        isSubmitting = true;
        
        const postId = postIdInput.value;
        const formData = new FormData(this);
        
        const submitBtn = this.querySelector('.btn-delete');
        const originalText = submitBtn.textContent;
        
        // Показываем загрузку
        submitBtn.textContent = 'Удаление...';
        submitBtn.disabled = true;
        
        try {
            console.log('Отправка запроса на удаление поста:', postId);
            
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData
            });
            
            const responseText = await response.text();
            console.log('Ответ сервера:', responseText);
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('Ошибка парсинга JSON:', jsonError);
                throw new Error('Некорректный ответ сервера');
            }
            
            if (data.success) {
                // УСПЕХ
                console.log('Пост удален:', data);
                
                // Закрываем модалку
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                
                // Показываем уведомление
                showNotification('Пост успешно удален', 'success');
                
                // Удаляем пост из DOM
                const postElement = document.querySelector(`[data-post-id="${postId}"]`)?.closest('.post');
                if (postElement) {
                    postElement.style.opacity = '0';
                    postElement.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        postElement.remove();
                    }, 300);
                } else {
                    // Или перезагружаем страницу
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
                
            } else {
                showNotification('Ошибка: ' + (data.error || 'Не удалось удалить пост'), 'error');
            }
            
        } catch (error) {
            console.error('Ошибка:', error);
            showNotification('Ошибка сети: ' + error.message, 'error');
        } finally {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            isSubmitting = false; // Сбрасываем флаг
        }
    });
    
    // Закрытие модалки
    const closeBtn = modal.querySelector('.close-modal');
    const cancelBtn = modal.querySelector('.btn-cancel');
    
    const closeModal = function() {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    };
    
    if (closeBtn) {
        // Удаляем старые обработчики
        closeBtn.replaceWith(closeBtn.cloneNode(true));
        const newCloseBtn = modal.querySelector('.close-modal');
        newCloseBtn.addEventListener('click', closeModal);
    }
    
    if (cancelBtn) {
        cancelBtn.replaceWith(cancelBtn.cloneNode(true));
        const newCancelBtn = modal.querySelector('.btn-cancel');
        newCancelBtn.addEventListener('click', closeModal);
    }
    
    // Закрытие при клике вне модалки
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
}

// Инициализация при загрузке (ОДИН раз!)
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM загружен, инициализация модалки удаления...');
    setupDeletePostModal();
});