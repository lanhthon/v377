.button-container {
background-color: #2D3748;
/* Slightly lighter dark background for the container */
padding: 30px;
border-radius: 12px;
box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
display: flex;
flex-wrap: wrap;
/* Allow buttons to wrap on smaller screens */
gap: 20px;
/* Space between buttons */
justify-content: center;
max-width: 90%;
/* Limit width on very large screens */
/* THÊM CÁC THUỘC TÍNH NÀY ĐỂ CỐ ĐỊNH MENU */
position: fixed; /* Làm cho phần tử cố định trên màn hình */
top: 0; /* Đặt ở phía trên cùng của màn hình */
left: 50%; /* Căn giữa theo chiều ngang */
transform: translateX(-50%); /* Dịch chuyển ngược lại 50% chiều rộng của nó để căn giữa hoàn hảo */
width: 100%; /* Chiếm toàn bộ chiều rộng */
z-index: 1000; /* Đảm bảo menu nằm trên các phần tử khác */
max-width: 100%; /* Đảm bảo nó không bị giới hạn chiều rộng trên màn hình nhỏ */
border-radius: 0 0 12px 12px; /* Nếu bạn muốn bo tròn chỉ ở phía dưới khi cố định */
}

/* Thêm padding cho body để nội dung không bị che bởi menu cố định */
body {
padding-top: 100px; /* Điều chỉnh giá trị này tùy theo chiều cao thực tế của .button-container */
}

.quick-access-btn {
background-color: #4CAF50;
/* Green */
color: white;
padding: 15px 25px;
border-radius: 8px;
text-decoration: none;
font-weight: 600;
display: flex;
align-items: center;
justify-content: center;
min-width: 180px;
/* Ensure buttons have a minimum width */
text-align: center;
transition: background-color 0.3s ease, transform 0.2s ease;
}

.quick-access-btn:hover {
background-color: #45A049;
/* Darker green on hover */
transform: translateY(-3px);
/* Slight lift effect */
}

.quick-access-btn i {
margin-right: 10px;
font-size: 1.2em;
/* Slightly larger icon */
}

.info-text {
width: 100%;
text-align: center;
margin-top: 20px;
color: #9CA3AF;
/* Gray text */
font-size: 0.9em;
}