CREATE TABLE `users` (
  `name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `email` varchar(200) NOT NULL,
  UNIQUE KEY(`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

