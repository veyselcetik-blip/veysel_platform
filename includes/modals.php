<div class="modal-overlay" id="loginModal">
    <div class="modal-content modal-split">
        <div class="modal-left" style="background-image: url('https://images.unsplash.com/photo-1599691873212-e8b8a6435a2d?q=80&w=774');">
            <h3>Hoş Geldiniz!</h3>
            <p>Yaratıcı projeler ve yetenekli tasarımcılar sizi bekliyor.</p>
        </div>
        <div class="modal-right">
            <button class="modal-close">&times;</button>
            <div class="form-container" style="box-shadow: none; padding: 0;">
                <h2 class="section-title" style="margin-bottom: 0.5rem;">Giriş Yap</h2>
                <p style="margin-bottom: 2rem; text-align: center;">Hesabınıza erişim sağlayın.</p>
                <form method="POST" action="login.php">
                    <div class="form-group"><div class="input-group"><i class="fas fa-envelope"></i><input type="email" name="email" placeholder="Email Adresiniz" required></div></div>
                    <div class="form-group"><div class="input-group"><i class="fas fa-lock"></i><input type="password" name="password" placeholder="Şifreniz" required></div></div>
                    
                    <div class="form-options">
                        <a href="forgot-password.php">Şifremi Unuttum?</a>
                    </div>
                    <?php if(($site_settings['recaptcha_enabled'] ?? 0) == 1 && !empty($site_settings['recaptcha_site_key'])): ?>
                    <div class="form-group">
                        <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($site_settings['recaptcha_site_key']) ?>"></div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group"><button type="submit" class="btn btn-primary" style="width: 100%;">Giriş Yap</button></div>
                </form>
                <p style="text-align: center; margin-top: 1rem;">Hesabın yok mu? <a href="#" id="switchToRegister">Hemen Kayıt Ol</a></p>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="registerModal">
    <div class="modal-content modal-split">
        <div class="modal-left" style="background-image: url('https://images.unsplash.com/photo-1558591710-4b4a1ae0f04d?q=80&w=774');">
            <h3>Fikrini Hayata Geçir</h3>
            <p>Projeni başlat, onlarca tasarım arasından en iyisini seç.</p>
        </div>
        <div class="modal-right">
            <button class="modal-close">&times;</button>
            <div class="form-container" style="box-shadow: none; padding: 0;">
                <h2 class="section-title" style="margin-bottom: 0.5rem;">Kayıt Ol</h2>
                <p style="margin-bottom: 2rem; text-align: center;">Yeni bir hesap oluşturun.</p>
                <form method="POST" action="register.php">
                     <div class="form-group"><div class="input-group"><i class="fas fa-user"></i><input type="text" name="username" placeholder="Kullanıcı Adınız" required></div></div>
                    <div class="form-group"><div class="input-group"><i class="fas fa-envelope"></i><input type="email" name="email" placeholder="Email Adresiniz" required></div></div>
                    <div class="form-group"><div class="input-group"><i class="fas fa-lock"></i><input type="password" name="password" placeholder="Şifreniz" required></div></div>
                    
                    <?php if(($site_settings['recaptcha_enabled'] ?? 0) == 1 && !empty($site_settings['recaptcha_site_key'])): ?>
                    <div class="form-group">
                        <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($site_settings['recaptcha_site_key']) ?>"></div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group"><button type="submit" class="btn btn-primary" style="width: 100%;">Hesabımı Oluştur</button></div>
                </form>
                <p style="text-align: center; margin-top: 1rem;">Zaten bir hesabın var mı? <a href="#" id="switchToLogin">Giriş Yap</a></p>
            </div>
        </div>
    </div>
</div>

<?php if(($site_settings['recaptcha_enabled'] ?? 0) == 1 && !empty($site_settings['recaptcha_site_key'])): ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>