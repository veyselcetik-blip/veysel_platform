// script.js (TAM VE GÜNCEL HALİ)

document.addEventListener('DOMContentLoaded', () => {

    // ======================================================
    // BÖLÜM 1: MODAL YÖNETİMİ VE GENEL FONKSİYONLAR
    // ======================================================

    const openModal = (modal) => {
        if (modal) modal.classList.add('active');
    };
    const closeModal = (modal) => {
        if (modal) modal.classList.remove('active');
    };
    const closeAllModals = () => {
        document.querySelectorAll('.modal-overlay.active').forEach(closeModal);
    };

    // --- Element Seçimleri ve Olay Atamaları ---
    const loginModal = document.getElementById('loginModal');
    const registerModal = document.getElementById('registerModal');
    const proposalModal = document.getElementById('proposalModal');
    const reportModal = document.getElementById('reportModal');

    // Genel modal açma butonları (ID ile)
    const setupOpenModalButton = (buttonId, modal) => {
        const button = document.getElementById(buttonId);
        if (button) {
            button.addEventListener('click', () => openModal(modal));
        }
    };
    
    setupOpenModalButton('openLoginModalBtn', loginModal);
    setupOpenModalButton('openProposalModalBtn', proposalModal);

    // Diğer modal açma butonları (Birden fazla olabilir)
    document.querySelectorAll('.open-register-modal').forEach(btn => {
        btn.addEventListener('click', () => openModal(registerModal));
    });
    
    // Modallar arası geçiş linkleri
    const switchToRegisterLink = document.getElementById('switchToRegister');
    const switchToLoginLink = document.getElementById('switchToLogin');

    if (switchToRegisterLink) {
        switchToRegisterLink.addEventListener('click', (e) => {
            e.preventDefault();
            closeAllModals();
            openModal(registerModal);
        });
    }

    if (switchToLoginLink) {
        switchToLoginLink.addEventListener('click', (e) => {
            e.preventDefault();
            closeAllModals();
            openModal(loginModal);
        });
    }

    // --- Genel Kapatma Olayları ---
    document.querySelectorAll('.modal-close').forEach(button => button.addEventListener('click', closeAllModals));
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeAllModals();
        });
    });
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeAllModals();
    });
    
    // ======================================================
    // BÖLÜM 2: DOSYA YÜKLEYİCİ VE SUNUM FORMU (project-detail.php için)
    // ======================================================
    const proposalForm = document.getElementById('proposal-form');
    if (proposalForm) {
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('design_file_input');
        const progressBar = document.getElementById('upload-progress-bar');
        const progressContainer = document.getElementById('upload-progress-container');
        const filePreview = document.getElementById('file-preview');
        const uploadedFilepathInput = document.getElementById('uploaded_filepath');
        const submitBtn = document.getElementById('submit-proposal-btn');
        const submitHelperText = document.getElementById('submit-helper-text');

        // Gerekli tüm HTML elementlerinin varlığını kontrol et
        if (dropZone && fileInput && submitBtn && submitHelperText) {
            
            const triggerFileInput = () => fileInput.click();
            const preventDefaults = (e) => { e.preventDefault(); e.stopPropagation(); };
            const highlight = () => dropZone.classList.add('dragover');
            const unhighlight = () => dropZone.classList.remove('dragover');

            const handleFileUpload = (file) => {
                if (!file) return;

                // Dosya tipi kontrolü
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Geçersiz dosya türü. Lütfen sadece JPG, PNG veya GIF yükleyin.');
                    return;
                }

                filePreview.innerHTML = '';
                submitBtn.disabled = true;
                submitHelperText.style.display = 'block';
                submitHelperText.textContent = 'Lütfen önce bir dosya yükleyin.';

                const reader = new FileReader();
                reader.onload = (e) => {
                    filePreview.innerHTML = `<img src="${e.target.result}" alt="Dosya Önizlemesi">`;
                };
                reader.readAsDataURL(file);

                const formData = new FormData();
                formData.append('design_file', file);
                
                // AJAX ile dosyayı yükle
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'actions/ajax_upload_handler.php', true);

                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        if(progressContainer) progressContainer.style.display = 'block';
                        if(progressBar) progressBar.style.width = percent + '%';
                    }
                };
                
                xhr.onload = () => {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.status === 'success') {
                                if(uploadedFilepathInput) uploadedFilepathInput.value = response.filepath;
                                submitBtn.disabled = false;
                                submitHelperText.textContent = 'Dosya yüklendi, sunumu gönderebilirsiniz.';
                            } else {
                                if(filePreview) filePreview.innerHTML = `<p style="color:red;">${response.message}</p>`;
                            }
                        } catch (e) {
                           if(filePreview) filePreview.innerHTML = `<p style="color:red;">Sunucudan geçersiz yanıt alındı.</p>`;
                        }
                    } else {
                        if(filePreview) filePreview.innerHTML = `<p style="color:red;">Yükleme hatası oluştu. (Hata Kodu: ${xhr.status})</p>`;
                    }
                };
                xhr.send(formData);
            };

            const handleDrop = (e) => handleFileUpload(e.dataTransfer.files[0]);
            const handleFileSelect = () => handleFileUpload(fileInput.files[0]);

            dropZone.addEventListener('click', triggerFileInput);
            fileInput.addEventListener('change', handleFileSelect);
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(e => dropZone.addEventListener(e, preventDefaults));
            ['dragenter', 'dragover'].forEach(e => dropZone.addEventListener(e, highlight));
            ['dragleave', 'drop'].forEach(e => dropZone.addEventListener(e, unhighlight));
            dropZone.addEventListener('drop', handleDrop);
        }

        proposalForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('submit-proposal-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Gönderiliyor...';

            const formData = new FormData(this);
            fetch('actions/ajax_submit_proposal.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Sunumunuz başarıyla gönderildi!');
                    window.location.reload();
                } else {
                    alert('Hata: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Sunumumu Gönder';
                }
            })
            .catch(error => {
                console.error("Fetch Hatası:", error);
                alert('Sunucuyla iletişim kurulamadı. Ağ hatası olabilir.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Sunumumu Gönder';
            });
        });
    }

    // ======================================================
    // BÖLÜM 3: DİĞER SAYFA ÖZELLİKLERİ
    // ======================================================
    
    // --- Sekmeli (Tab) Yapı ---
    const tabButtons = document.querySelectorAll('.tab-btn');
    if (tabButtons.length > 0) {
        const tabContents = document.querySelectorAll('.tab-content');
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                button.classList.add('active');
                const targetTab = document.getElementById('tab-' + button.dataset.tab);
                if(targetTab) targetTab.classList.add('active');
            });
        });
    }

    // --- Profil Menüsü ---
    const profileDropdownBtn = document.getElementById('profileDropdownBtn');
    if (profileDropdownBtn) {
        const profileDropdownContent = document.getElementById('profileDropdownContent');
        if(profileDropdownContent){
            profileDropdownBtn.addEventListener('click', (event) => {
                event.stopPropagation();
                profileDropdownContent.classList.toggle('show');
            });
        }
    }
});

// Sayfa dışına tıklandığında çalışan olaylar (DOMContentLoaded dışında kalabilir)
window.addEventListener('click', (event) => {
    // Profil menüsünü kapat
    const profileDropdownContent = document.getElementById('profileDropdownContent');
    const profileDropdownBtn = document.getElementById('profileDropdownBtn');
    if (profileDropdownContent && profileDropdownBtn && profileDropdownContent.classList.contains('show')) {
        if (!profileDropdownContent.contains(event.target) && !profileDropdownBtn.contains(event.target)) {
            profileDropdownContent.classList.remove('show');
        }
    }
});