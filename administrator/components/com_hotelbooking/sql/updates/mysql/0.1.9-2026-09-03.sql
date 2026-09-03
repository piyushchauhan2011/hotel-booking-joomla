-- Extra rooms for Chiang Mai Riverside Retreat so the hotel landing rooms module shows a grid.

INSERT INTO `#__hotelbooking_rooms`
    (`id`, `destination_id`, `name`, `alias`, `language`, `description`, `price`, `capacity`, `image`, `published`, `ordering`)
VALUES
    (9103, 9001, 'Riverside Suite', 'riverside-suite', 'en-GB',
        '<p>A king suite with a private balcony over the Ping River and a soaking tub.</p>',
        3200.00, 2, 'media/com_hotelbooking/images/rooms/room-classic.svg', 1, 2),
    (9104, 9002, 'สวีทริมน้ำ', 'riverside-suite', 'th-TH',
        '<p>สวีทเตียงคิงพร้อมระเบียงส่วนตัวหันสู่แม่น้ำปิงและอ่างแช่ตัว.</p>',
        3200.00, 2, 'media/com_hotelbooking/images/rooms/room-classic.svg', 1, 2),
    (9105, 9001, 'Family Bungalow', 'family-bungalow', 'en-GB',
        '<p>A two-bedroom garden bungalow with a sitting area, ideal for families.</p>',
        4200.00, 4, 'media/com_hotelbooking/images/rooms/room-city.svg', 1, 3),
    (9106, 9002, 'บังกะโลครอบครัว', 'family-bungalow', 'th-TH',
        '<p>บังกะโลสวนสองห้องนอนพร้อมมุมนั่งเล่น เหมาะสำหรับครอบครัว.</p>',
        4200.00, 4, 'media/com_hotelbooking/images/rooms/room-city.svg', 1, 3);

UPDATE `#__hotelbooking_rooms`
SET `image` = 'media/com_hotelbooking/images/rooms/room-garden.svg'
WHERE `id` IN (9101, 9102) AND (`image` = '' OR `image` IS NULL);

INSERT INTO `#__associations` (`id`, `context`, `key`)
VALUES
    (9103, 'com_hotelbooking.item.room', MD5('hb-demo-room-9103-9104')),
    (9104, 'com_hotelbooking.item.room', MD5('hb-demo-room-9103-9104')),
    (9105, 'com_hotelbooking.item.room', MD5('hb-demo-room-9105-9106')),
    (9106, 'com_hotelbooking.item.room', MD5('hb-demo-room-9105-9106'));
