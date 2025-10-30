<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Đăng Nhập Hệ Thống - CÔNG TY TNHH SX & UD VẬT LIỆU XANH 3I</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
    body {
        font-family: 'Be Vietnam Pro', sans-serif;
        background-color: #1e293b;
    }

    .input-line {
        background: transparent;
        border: none;
        border-bottom: 1px solid #475569;
        /* THAY ĐỔI: Chữ khi người dùng nhập cũng màu xanh */
        color: #8EC14A; 
        width: 100%;
        padding: 0.5rem 0;
        outline: none;
        transition: border-color 0.3s ease;
        font-size: 1rem;
    }

    .input-line::placeholder {
        color: #8EC14A;
        opacity: 0.7;
        font-weight: 300;
    }

    .input-line:focus {
        border-bottom-color: #8EC14A;
    }

    .btn {
        padding: 0.75rem 2.5rem;
        border-radius: 6px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .btn-primary {
        background-color: #8EC14A;
        color: #0f172a;
        border: 1px solid #8EC14A;
    }

    .btn-primary:hover {
        background-color: #a3e635;
        border-color: #a3e635;
    }

    .btn-primary:disabled {
        background-color: #475569;
        border-color: #475569;
        color: #94a3b8;
        cursor: not-allowed;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .form-element {
        animation: fadeIn 0.5s ease forwards;
        opacity: 0;
    }
    
    .text-balance {
        text-wrap: balance;
    }
    </style>
    <link rel="canonical" href="https://3igreen.com.vn/">

<!-- Open Graph (Facebook, Zalo…) -->
<meta property="og:title" content="3i-Fix | Phần mềm Quản lý 3I GREEN">
<meta property="og:description" content="Công ty TNHH Sản xuất và Ứng dụng Vật liệu xanh 3I">
<meta property="og:image" content="../bg.png">
<meta property="og:url" content="https://3igreen.com.vn/">
<meta property="og:type" content="website">
<meta property="og:site_name" content="3i-Fix">
<meta property="og:locale" content="vi_VN">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="3i-Fix | Phần mềm Quản lý 3I GREEN">
<meta name="twitter:description" content="Công ty TNHH Sản xuất và Ứng dụng Vật liệu xanh 3I">
<meta name="twitter:image" content="../bg.png">

<!-- Favicon (tùy chọn) -->
<link rel="icon" href="https://3igreen.com.vn/favicon.ico">
<link rel="apple-touch-icon" href="../logo.png">
</head>

<body class="bg-slate-800 min-h-screen flex flex-col">

    <main class="flex-grow flex items-center justify-center p-4">
        <div
            class="w-full max-w-4xl bg-slate-900/50 backdrop-blur-lg border border-slate-700 grid grid-cols-1 md:grid-cols-2 shadow-2xl shadow-green-500/20 rounded-xl overflow-hidden">
            <div
                class="flex flex-col items-center justify-center text-white p-8 md:border-r border-b md:border-b-0 border-slate-700">
                
                <div class="h-48 w-48 bg-white rounded-full mb-6 shadow-lg flex items-center justify-center">
                    <img src="../logo.png" class="w-40 h-auto" alt="Logo" />
                </div>
                
                <h1 class="text-lg md:text-xl leading-relaxed tracking-wider font-semibold text-center uppercase text-[#8EC14A] text-balance">
                    Công ty TNHH Sản xuất và Ứng dụng Vật liệu xanh 3I
                </h1>
            </div>
            <div class="p-8 sm:p-12 flex flex-col justify-center text-white">
                
                <h2 class="text-2xl font-bold mb-6 text-center text-[#8EC14A] form-element" style="animation-delay: 0.1s;">
                    ĐĂNG NHẬP
                </h2>
                
                <div id="error-message"
                    class="text-red-400 text-center mb-4 font-medium text-sm transition-opacity duration-300 opacity-0">
                </div>
                <form id="login-form">
                    <div class="mb-6 form-element" style="animation-delay: 0.2s;">
                        <input type="text" id="username" placeholder="Tên đăng nhập" class="input-line" required />
                    </div>
                    <div class="mb-6 form-element" style="animation-delay: 0.3s;">
                        <input type="password" id="password" placeholder="Mật khẩu" class="input-line" required />
                    </div>
                    <div class="flex justify-center mt-10 form-element" style="animation-delay: 0.4s;">
                        <button type="submit" id="login-button" class="btn btn-primary">Đăng nhập</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <footer class="text-center text-slate-500 text-xs md:text-sm p-4">
        Copyright © 3IGREEN
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const loginForm = document.getElementById('login-form');
        const loginButton = document.getElementById('login-button');
        const errorMessageDiv = document.getElementById('error-message');
        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            errorMessageDiv.style.opacity = '0';
            errorMessageDiv.textContent = '';
            if (!username || !password) {
                showError('Vui lòng nhập đủ thông tin.');
                return;
            }
            loginButton.disabled = true;
            loginButton.textContent = 'Đang xử lý...';
            try {
                const response = await fetch('../api/handle_login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username,
                        password
                    })
                });
                const result = await response.json();
                if (result.success) {
                    window.location.href = '../index.php';
                } else {
                    showError(result.message);
                }
            } catch (error) {
                showError('Lỗi kết nối đến máy chủ. Vui lòng thử lại.');
            } finally {
                loginButton.disabled = false;
                loginButton.textContent = 'Đăng nhập';
            }
        });

        function showError(message) {
            errorMessageDiv.textContent = message;
            errorMessageDiv.style.opacity = '1';
        }
    });
    </script>
</body>

</html>