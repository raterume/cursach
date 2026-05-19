<?php
    require_once 'init.php'; //сессия
    redirectIfNotLoggedIn(); 
    require_once 'class/follows.php';
    require_once 'includes/follows_function.php'; 

    $current_user = getCurrentUser();
    $conn = get_db_connection();
    $follows = new follows($conn);
    $follows_result = $follows->getFollowings($current_user['id']);

    // По умолчанию показываем подписок
$show_search_results = false;
$search_results = null;
// обработка поиска
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = trim($_GET['search']);
    $search_results = $follows->searchUsers($search_term, $current_user['id'], 50);
    $show_search_results = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/style_main.css">
    <title>Document</title>
</head>
<body>
<div class="page-wrapper">
<div class="container">
        <!-- Левая боковая панель -->
        <?php require_once 'left_panel.php'?>

        <!-- Основная область контента -->
<main class="content">
    <div class="feed">
        <!-- поиск -->
        <div class="serch">
            <form method="GET" class="serch" action="" id="search-form">
                <input type="text" name="search" class="serch-user" required placeholder="найти кого-нибудь" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="action-btn search-btn"></button>
            </form>
        </div>

        <?php if (!$show_search_results): error_log("fff");?>
            <!-- пользователи -->
            <?php if ($follows_result->num_rows > 0): 
                while ($follows_row = $follows_result->fetch_assoc()):?>
                    <article class="follow-pers">
                        <?php   echo renderfollowers($follows_row, $current_user['id']);?>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                    <div class="no-posts">
                        <p>тут пока никого нет...</p>
                    </div>
            <?php endif; ?>

        <?php else: ?>

            <?php if ($search_results->num_rows > 0): 
                while ($follows_row = $search_results->fetch_assoc()):?>
                    <article class="follow-pers">
                       <?php   echo renderfollowers($follows_row, $current_user['id']);?>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                    <div class="no-posts">
                        <p>никого...</p>
                    </div>
            <?php endif; ?>
        <?php endif; ?>



    </div>
</main>
    <?php require_once 'right_panel.php'?>
</div>
</div>
