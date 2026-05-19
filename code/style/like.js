document.addEventListener('DOMContentLoaded', function() {
    // кнопка 
    const likeButtons = document.querySelectorAll('.like-btn');
    
    // Обработчик 
    likeButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            // event.preventDefault(); 
            
            // Переключаем класс active
            this.classList.toggle('liked');
            
            //  счетчик
            const currentCount = parseInt(this.textContent.trim()) || 0;
            if (this.classList.contains('liked')) {
                this.textContent = (currentCount + 1).toString();
            } else {
                this.textContent = (currentCount - 1).toString();
            }
    
            // анимация
            this.style.transform = 'scale(1.1)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
            
        });
    });
});