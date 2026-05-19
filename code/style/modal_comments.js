// Обработка комментариев
document.addEventListener('DOMContentLoaded', function() {
    setupCommentModals();
});

function setupCommentModals() {
    // 1. Редактирование комментария
    setupEditCommentModal();
    
    // 2. Удаление комментария
    setupDeleteCommentModal();
}

// Редактирование комментария
function setupEditCommentModal() {
    const modal = document.getElementById('modal-edit-comment');
    const form = document.getElementById('form-edit-comment');
    const textarea = document.getElementById('edit-comment-text');
    const charCount = document.getElementById('edit-comment-char-count');
    const commentIdInput = document.getElementById('edit-comment-id');
    
    if (!modal || !form || !textarea) {
        console.error('Не найдены элементы модалки редактирования комментария');
        return;
    }
    
    // Инициализация счетчика символов
    function updateCharCount() {
        if (!charCount) return;
        const length = textarea.value.length;
        charCount.textContent = length;
        charCount.className = '';
        if (length > 900) charCount.classList.add('warning');
        if (length > 980) charCount.classList.add('error');
    }
    
    textarea.addEventListener('input', updateCharCount);
    
    // Обработка кликов по кнопкам редактирования комментариев
    document.addEventListener('click', function(e) {
        const editBtn = e.target.closest('.edit-comment-btn, .setting-comm-btn');
        if (editBtn && (editBtn.classList.contains('edit-comment-btn') || 
                        editBtn.classList.contains('setting-comm-btn'))) {
            e.preventDefault();
            e.stopPropagation();
            
            const commentId = editBtn.dataset.commentId;
            const commentText = editBtn.dataset.commentText || '';
            
            console.log('Редактирование комментария:', { 
                commentId, 
                commentText: commentText.substring(0, 50) + '...'
            });
            
            // Заполняем форму
            commentIdInput.value = commentId;
            textarea.value = commentText;
            
            // Обновляем счетчик символов
            updateCharCount();
            
            // Открываем модалку
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            // Фокус на текстовое поле
            setTimeout(() => {
                if (textarea) textarea.focus();
            }, 100);
        }
    });
    
    // Обработка отправки формы
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('Отправка формы редактирования комментария');
        
        const text = textarea.value.trim();
        const commentId = commentIdInput.value;
        
        console.log('Данные для отправки:', {
            commentId,
            textLength: text.length
        });
        
        // Валидация
        if (!text) {
            showNotification('Комментарий не может быть пустым', 'error');
            return;
        }
        
        if (!commentId || commentId <= 0) {
            showNotification('Неверный ID комментария', 'error');
            return;
        }
        
        const formData = new FormData(this);
        
        const submitBtn = this.querySelector('.btn-save');
        const originalText = submitBtn.textContent;
        
        // Показываем загрузку
        submitBtn.textContent = 'Сохранение...';
        submitBtn.disabled = true;
        
        try {
            console.log('Отправка редактирования комментария на:', this.action);
            
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData
            });
            
            const responseText = await response.text();
            console.log('Ответ сервера:', responseText.substring(0, 200));
            
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
                console.log('Комментарий обновлен:', data);
                
                // Закрываем модалку
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                
                // Обновляем текст комментария на странице
                const commentElement = document.querySelector(`[data-comment-id="${commentId}"] .comment-text`);
                if (commentElement) {
                    commentElement.innerHTML = data.text.replace(/\n/g, '<br>');
                    showNotification('Комментарий обновлен!', 'success');
                } else {
                    // Если не нашли элемент, перезагружаем страницу
                    showNotification('Комментарий обновлен!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
                
                // Очищаем форму
                textarea.value = '';
                if (charCount) {
                    charCount.textContent = '0';
                    charCount.className = '';
                }
                
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
    
    // Закрытие модалки
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
    
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
}

// Удаление комментария
function setupDeleteCommentModal() {
    const modal = document.getElementById('modal-delete-comment');
    const form = document.getElementById('form-delete-comment');
    const commentIdInput = document.getElementById('delete-comment-id');
    
    if (!modal || !form) {
        console.error('Не найдены элементы модалки удаления комментария');
        return;
    }
    
    // Обработка кликов по кнопкам удаления комментариев
    document.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.delete-comment-btn, .drop-comm-btn');
        if (deleteBtn) {
            e.preventDefault();
            e.stopPropagation();
            
            const commentId = deleteBtn.dataset.commentId;
            console.log('Удаление комментария ID:', commentId);
            
            if (!commentId || commentId <= 0) {
                showNotification('Неверный ID комментария', 'error');
                return;
            }
            
            // Заполняем форму
            commentIdInput.value = commentId;
            
            // Открываем модалку
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    });
    
    // Обработка отправки формы
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const commentId = commentIdInput.value;
        const formData = new FormData(this);
        
        const submitBtn = this.querySelector('.btn-delete');
        if (!submitBtn) {
            console.error('Не найдена кнопка отправки в форме удаления комментария');
            showNotification('Ошибка: не найдена кнопка отправки', 'error');
            return;
        }
        
        const originalText = submitBtn.textContent;
        
        // Показываем загрузку
        submitBtn.textContent = 'Удаление...';
        submitBtn.disabled = true;
        
        try {
            console.log('Отправка запроса на удаление комментария:', commentId);
            
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
                console.log('Комментарий удален:', data);
                
                // Закрываем модалку
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                
                // Удаляем комментарий из DOM с анимацией
                const commentElement = document.querySelector(`[data-comment-id="${commentId}"]`);
                if (commentElement) {
                    commentElement.style.transition = 'all 0.3s ease';
                    commentElement.style.opacity = '0';
                    commentElement.style.transform = 'translateX(-20px)';
                    commentElement.style.height = '0';
                    commentElement.style.margin = '0';
                    commentElement.style.padding = '0';
                    commentElement.style.overflow = 'hidden';
                    
                    setTimeout(() => {
                        commentElement.remove();
                        showNotification('Комментарий удален', 'success');
                    }, 300);
                } else {
                    showNotification('Комментарий удален', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
                
            } else {
                showNotification('Ошибка: ' + (data.error || 'Не удалось удалить комментарий'), 'error');
            }
            
        } catch (error) {
            console.error('Ошибка:', error);
            showNotification('Ошибка сети: ' + error.message, 'error');
        } finally {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    });
    
    // Закрытие модалки
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
    
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
}

// Функция уведомлений (если еще нет в другом файле)
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