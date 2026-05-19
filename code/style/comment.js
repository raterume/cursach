document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.comment-btn').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            
            // Ищем ближайший блок комментариев внутри того же поста
            const post = this.closest('.post');
            const commentSection = post.querySelector('.comment-section');
            
            if (!commentSection) return;
            
            
            if (commentSection.style.display === 'none' || commentSection.style.display === '') {
                commentSection.style.display = 'block';
                commentSection.classList.remove('hiding');
                this.classList.add('active');
                
            } else {
                commentSection.classList.add('hiding');
                this.classList.remove('active');
                
                setTimeout(() => {
                    commentSection.style.display = 'none';
                    commentSection.classList.remove('hiding');
                }, 300);
            }
        });
    });
});