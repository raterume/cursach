<?php 
    require_once 'class/user.php'; 

$user_obj = new User($conn);
$profile_user = $user_obj->getUserById($_SESSION['user']['id']);
$user_row = $profile_user->fetch_assoc();

$react_user = $user_obj->getActivity($_SESSION['user']['id']);

?> 

        <aside class="right-sidebar">

            <div class="user-info">         
                    <img class="user-avatar2" src="<?php echo htmlspecialchars($user_row['avatar'] ? 'pic/' . $user_row['avatar'] : 'pic/ico.jpg'); ?>">
                    <h3 class="user-name2"><?php echo $_SESSION['user']['login']?></h3>
            </div>

            <div class="logout"><a href="logout.php" class="a-logout">выйти</a></div>



           <?php if ($react_user->num_rows > 0): ?>
                       <div class="user-info" style = "margin-top: 10px;">         
        <?php while ($react= $react_user->fetch_assoc()):?>
       
                    <article class = "reaction">
                        <div>
                            <img class="react-avatar" src="<?php echo htmlspecialchars($react['actor_avatar'] ? 'pic/' . $react['actor_avatar']  : 'pic/ico.jpg'); ?>">
                        </div>
                        <div class = "react-inf">
            <a href="profile.php?id=<?php echo $react['actor_id']; ?>" class="react-link">
                <?php echo htmlspecialchars($react['actor_login']) ?>
            </a>
                            <span> <?php echo htmlspecialchars($react['message']) ?></span>
                        </div>
                    </article>


            <?php endwhile; ?>
            </div>
              <?php endif; ?>


        </aside>
