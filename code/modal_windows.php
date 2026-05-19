<!-- Модальное окно редактирования комментария -->
<div id="modal-edit-comment" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Редактировать комментарий</h2>
        
        <form id="form-edit-comment" method="POST" action="includes/edit_comment.php">
            <input type="hidden" name="comment_id" id="edit-comment-id" value="">
            
            <div class="form-group">
                <label for="edit-comment-text">Текст комментария:</label>
                <textarea id="edit-comment-text" name="text" 
                          placeholder="Введите текст комментария..." 
                          rows="4" maxlength="1000" class="form-control"></textarea>
                <div class="char-counter">
                    <span id="edit-comment-char-count">0</span>/1000
                </div>
            </div>
            
            <div class="modal-buttons">
                <button type="button" class="btn-cancel">Отмена</button>
                <button type="submit" class="btn-save">Сохранить</button>
            </div>
        </form>
    </div>
</div>


<!-- Модальное окно удаления комментария -->
<div id="modal-delete-comment" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Удалить комментарий</h2>
        
        <div class="confirmation-message">
            <p>Вы уверены, что хотите удалить этот комментарий?</p>
            <p class="warning-text">Это действие нельзя отменить.</p>
        </div>
        
        <form id="form-delete-comment" method="POST" action="includes/delete_comment.php">
            <input type="hidden" name="comment_id" id="delete-comment-id" value="">
            
            <div class="modal-buttons">
                <button type="button" class="btn-cancel">Отмена</button>
                <button type="submit" class="btn-save btn-delete">Удалить</button>
            </div>
        </form>
    </div>
</div>
<script src="style/modal_comments.js"></script>


<!-- Модальное окно для редактирования поста -->
<div id="modal-edit-post" class="modal">
    <div class="modal-content modal-wide">
        <span class="close-modal">&times;</span>
        <h2>Редактировать пост</h2>
        
        <form id="form-edit-post" method="POST" action="includes/edit_post.php" enctype="multipart/form-data">
            <input type="hidden" name="post_id" id="edit-post-id" value="">
            
            <!-- Текст поста -->
            <div class="form-group">
                <label for="edit-post-text">Текст поста:</label>
                <textarea id="edit-post-text" name="text" 
                          placeholder="О чем думаете?" 
                          rows="4" maxlength="1000" class="form-control"></textarea>
                <div class="char-counter">
                    <span id="edit-char-count">0</span>/1000
                </div>
            </div>
            
            <!-- Существующие изображения -->
            <div class="form-group" id="existing-images-container">
                <label>Текущие изображения:</label>
                <div class="existing-images" id="existing-images">
                    <!-- Здесь JavaScript добавит существующие изображения -->
                </div>
            </div>
            
            <!-- Добавление новых изображений -->
            <div class="form-group">
                <label>Добавить новые изображения (до 4 всего):</label>
                <div class="image-upload-area">
                    <!-- Контейнер для превью новых изображений -->
                    <div class="image-previews" id="edit-image-previews">
                        <div class="image-preview-item empty">
                            <label for="edit-post-images" class="upload-label">
                                <div class="upload-placeholder">
                                    <span>+</span>
                                    <small>Добавить фото</small>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Скрытый input для файлов -->
                    <input type="file" id="edit-post-images" name="new_images[]" 
                           accept="image/*" multiple class="file-input-hidden">
                </div>
                <small class="help-text">Можно загрузить JPG, PNG, GIF (макс. 2MB каждое)</small>
            </div>
            
            <!-- Скрытое поле для удаленных изображений -->
            <input type="hidden" name="deleted_images" id="deleted-images" value="">
            
            <div class="modal-buttons">
                <button type="button" class="btn-cancel">Отмена</button>
                <button type="submit" class="btn-save">Сохранить изменения</button>
            </div>
        </form>
    </div>
</div>
<script src="style/modal_edit_post.js"></script>



<!-- Модальное окно подтверждения удаления -->
<div id="modal-delete-post" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Удалить пост</h2>
        
        <div class="confirmation-message">
            <p>Вы уверены, что хотите удалить этот пост?</p>
            <p class="warning-text">Это действие нельзя отменить.</p>
        </div>
        
        <form id="form-delete-post" method="POST" action="includes/delete_post.php">
            <input type="hidden" name="post_id" id="delete-post-id" value="">
            
            <div class="modal-buttons">
                <button type="button" class="btn-cancel">Отмена</button>
                <button type="submit" class="btn-delete">Удалить</button>
            </div>
        </form>
    </div>
</div>
<script src="style/modal_delete_post.js"></script>