// script.js (TÜM MODALLAR İÇİN EKSİKSİZ VE DÜZELTİLMİŞ NİHAİ SÜRÜM)

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

    // Sunum Gönder (ID ile)
    const openProposalBtn = document.getElementById('openProposalModalBtn');
    if(openProposalBtn) {
        openProposalBtn.addEventListener('click', () => openModal(proposalModal));
    }
    
    // === DÜZELTİLMİŞ BÖLÜM BAŞLANGICI ===
    // Class'a göre modal açma (Giriş Yap, Kayıt Ol vb.)
    document.querySelectorAll('.open-login-modal').forEach(btn => {
        btn.addEventListener('click', () => openModal(loginModal));
    });

    document.querySelectorAll('.open-register-modal').forEach(btn => {
        btn.addEventListener('click', () => openModal(registerModal));
    });
    // === DÜZELTİLMİŞ BÖLÜM SONU ===
    
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
    // BÖLÜM 2: DOSYA YÜKLEYİCİ VE SUNUM FORMU
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

        if (dropZone && fileInput && submitBtn && submitHelperText) {
            const triggerFileInput = () => fileInput.click();
            const preventDefaults = (e) => { e.preventDefault(); e.stopPropagation(); };
            const highlight = () => dropZone.classList.add('dragover');
            const unhighlight = () => dropZone.classList.remove('dragover');
            const handleFileUpload = (file) => {
                if (!file) return;
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
                reader.onload = (e) => { filePreview.innerHTML = `<img src="${e.target.result}" alt="Dosya Önizlemesi">`; };
                reader.readAsDataURL(file);
                const formData = new FormData();
                formData.append('design_file', file);
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

    // --- Proje Oluşturma Sayfası Yardımcısı ---
    const categorySelect = document.getElementById('category_id');
    const categoryDescriptionBox = document.getElementById('category-description');
    if (categorySelect && categoryDescriptionBox) {
        if (typeof categoriesData !== 'undefined') {
            categorySelect.addEventListener('change', function() {
                const selectedId = this.value;
                const descriptionSpan = categoryDescriptionBox.querySelector('span');
                if (selectedId && categoriesData[selectedId]) {
                    descriptionSpan.textContent = categoriesData[selectedId].description || 'Bu kategori için açıklama bulunmuyor.';
                } else {
                    descriptionSpan.textContent = 'Lütfen bir kategori seçerek başlayın.';
                }
            });
        }
    }

    // --- Ana Sayfa Animasyonlu Sayaç ---
    const counters = document.querySelectorAll('.stat-value, .stat-value-prize');
    if (counters.length > 0) {
        const speed = 200;
        const animateCounter = (counter) => {
            const target = +counter.getAttribute('data-target');
            const isPrize = counter.classList.contains('stat-value-prize');
            const updateCount = () => {
                const count = +counter.innerText.replace(/[^0-9]/g, '');
                const increment = target / speed;
                if (count < target) {
                    const newCount = Math.ceil(count + increment);
                    counter.innerText = isPrize ? '₺' + newCount.toLocaleString('tr-TR') : newCount;
                    setTimeout(updateCount, 15);
                } else {
                    counter.innerText = isPrize ? '₺' + target.toLocaleString('tr-TR') : target;
                }
            };
            updateCount();
        };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        counters.forEach(counter => { observer.observe(counter); });
    }

    // --- Ana Sayfa Canlı Akış Simülasyonu ---
    const liveFeedList = document.getElementById('live-feed-list');
    if (liveFeedList) {
        const activities = [
            { icon: 'fa-palette', text: '<strong>Yeni bir sunum</strong> eklendi: "Modern Kafe Logosu"' },
            { icon: 'fa-lightbulb', text: '<strong>Yeni bir proje</strong> başlatıldı: "E-Ticaret Sitesi Arayüzü"' },
            { icon: 'fa-crown', text: '<strong>Bir proje kazananını</strong> seçti: "Mobil Oyun Karakteri"' },
            { icon: 'fa-user-plus', text: '<strong>Yeni bir tasarımcı</strong> aramıza katıldı.' },
            { icon: 'fa-comments', text: '<strong>Bir projeye</strong> yeni yorum yapıldı.' },
        ];
        let activityIndex = 0;
        const addActivity = () => {
            if (liveFeedList.children.length >= 4) {
                liveFeedList.removeChild(liveFeedList.lastChild);
            }
            const activity = activities[activityIndex];
            const li = document.createElement('li');
            li.innerHTML = `<i class="fas ${activity.icon}"></i> ${activity.text}`;
            liveFeedList.prepend(li);
            setTimeout(() => { li.classList.add('visible'); }, 100);
            activityIndex = (activityIndex + 1) % activities.length;
        };
        setInterval(addActivity, 4000);
        addActivity();
    }
});

// Sayfa dışına tıklandığında çalışan olaylar
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