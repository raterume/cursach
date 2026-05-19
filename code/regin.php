<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/style_in.css">
    <title>Document</title>
</head>
<body>
    
 <div class="content">

         <div class="text">
            Регистрация
         </div>

             <?php if (!empty($errors['general'])): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($errors['general']); ?>
        </div>
    <?php endif; ?>

   <form action="regin_logic.php" method="POST">

    <!-- Почта -->         
            <div class="field">
               <input type="email" name="email" 
                     class="input <?php echo !empty($errors['email']) ? 'error' : ''; ?>" required placeholder="почта"
                     value="<?php echo htmlspecialchars($email ?? ''); ?>">
               <span class="fas fa-user"></span>
                  <?php if (!empty($errors['email'])): ?>
                     <div class="field-error"><?php echo htmlspecialchars($errors['email']); ?></div>
                  <?php endif; ?>
            </div>

   <!-- логин -->
            <div class="field">
               <input type="text" name="login" 
                  class="input <?php echo !empty($errors['login']) ? 'error' : ''; ?>" required placeholder="логин"
                  value="<?php echo htmlspecialchars($login ?? ''); ?>">
               <span class="fas fa-user"></span>
                  <?php if (!empty($errors['login'])): ?>
                     <div class="field-error"><?php echo htmlspecialchars($errors['login']); ?></div>
                  <?php endif; ?>
            </div>

   <!-- пароли -->
            <div class="field">
               <input type="password" name="password" class="input <?php echo !empty($errors['password']) ? 'error' : ''; ?>" required placeholder="пароль">
               <span class="fas fa-lock"></span>
                  <?php if (!empty($errors['password'])): ?>
                     <div class="field-error"><?php echo htmlspecialchars($errors['password']); ?></div>
                  <?php endif; ?>
            </div>

            <div class="field">
               <input type="password" name="password_confirm" class="input <?php echo !empty($errors['password_confirm']) ? 'error' : ''; ?>" required placeholder="повторите пароль">
               <span class="fas fa-lock"></span>
                  <?php if (!empty($errors['password_confirm'])): ?>
                     <div class="field-error"><?php echo htmlspecialchars($errors['password_confirm']); ?></div>
                  <?php endif; ?>
            </div>

            
<div class="field-сс">
      <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
      <div class="h-captcha" data-sitekey="b3c2f652-eecc-47a6-be3e-0568a6aaca2a"></div>
      <?php if (!empty($errors['captcha'])): ?>
         <div class="field-error"><?php echo htmlspecialchars($errors['captcha']); ?></div>
      <?php endif; ?>
</div>

            <button type="submit" class="submit">Вперед</button>

            <div class="sign">
               Уже есть аккаунт?
               <a href="login.php">войти</a>
            </div>
         </form>
      </div>

</body>
</html>