<?php
    require_once 'init.php';
    function renderProfileHeader($user,  $is_own_profile = false, ) {
    // HTML
    ob_start(); //буферизацию

            $is_following = false; //подписан ли 
        if ($user['id']) {
            $conn = get_db_connection(); 
            $stmt = $conn->prepare("SELECT id FROM subscriptions WHERE user = ? and subs = ?");
            $stmt->bind_param("ii", $_SESSION['user']['id'], $user['id']);
            $stmt->execute();
            $stmt->store_result();
            $is_following = $stmt->num_rows > 0;
            $p = $stmt->num_rows;
            $stmt->close();
        }
    ?>
        <article class="pers">
                <img class="back-img" src="<?php echo htmlspecialchars($user['backimg'] ? 'pic/' . $user['backimg'] : 'pic/back.jpg'); ?>">
            <div class="avatar-place">
                <div class="pers-avatar">
                    <img class="person-avatar" src="<?php echo htmlspecialchars($user['avatar'] ? 'pic/' . $user['avatar'] : 'pic/ico.jpg'); ?>">
                </div>
                <div class="person-btns">
        <?php if ($is_own_profile): ?>
        <!-- Свой профиль -->
         <a href="settings.php" class="follow-btn">Редактировать профиль</a>
         <?php else: ?>
        <!-- чужой -->
        <div class="f-button">
            <form action="includes/follow.php" method="POST">
            <input type="hidden" name="subs_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                <button type="submit" class="<?php echo $is_following ? 'unfollow-btn' : 'follow-btn'; ?>">
                    <?php echo $is_following ? 'отписаться' : 'подписаться';?>
                </button>
            </form>
        </div>
        <?php endif; ?>
                </div>
            </div>


            <div class="info">
                <h3 class="person-name"><?php echo htmlspecialchars($user['login']);?></h3>
                <div class="bio">
                    <span ><?php echo nl2br(htmlspecialchars($user['inform']));?></span>
                </div>


                <div class="followers">
                <a href="#" class="foll-link" onclick="showFollowing(<?php echo $user['id']; ?>)">
                    <span class="ers-num"><?php echo htmlspecialchars($user['podpiski']); ?></span>
                    <span class="foll-info"> Подписок</span>
                </a>

                
                <a href="#" class="foll-link" onclick="showFollowers(<?php echo $user['id']; ?>)">
                    <span class="ing-num"><?php echo htmlspecialchars($user['podpischiki']); ?></span>
                    <span class="foll-info"> Подписчиков</span>
                </a>
                </div>
            <div>
        </article>





        <!-- Модальное окно подписчиков -->
    <div id="modal-followers" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('modal-followers')">&times;</span>
            <h2>Подписчики</h2>
            <div id="followers-list" class="user-list">
                <div class="loading">Загрузка...</div>
            </div>
        </div>
    </div>

    <!-- Модальное окно подписок -->
    <div id="modal-following" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('modal-following')">&times;</span>
            <h2>Подписки</h2>
            <div id="following-list" class="user-list">
                <div class="loading">Загрузка...</div>
            </div>
        </div>
    </div>

    <script>
    function showFollowers(userId) {
        event.preventDefault();
        document.getElementById('modal-followers').style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Загружаем список подписчиков через AJAX
        fetch('includes/get_followers.php?user_id=' + userId)
            .then(response => response.text())
            .then(html => {
                document.getElementById('followers-list').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('followers-list').innerHTML = 'Ошибка загрузки';
            });
    }
    
    function showFollowing(userId) {
        event.preventDefault();
        document.getElementById('modal-following').style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Загружаем список подписок через AJAX
        fetch('includes/get_following.php?user_id=' + userId)
            .then(response => response.text())
            .then(html => {
                document.getElementById('following-list').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('following-list').innerHTML = 'Ошибка загрузки';
            });
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Закрытие при клике вне модального окна
    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            event.target.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
    </script>
    
    <?php
    return ob_get_clean();
}