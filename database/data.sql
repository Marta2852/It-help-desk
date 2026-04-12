INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `role`) VALUES
	(1, 'Test1', 'test@gmail.com', NULL, '$2y$12$QTyJAvDWAz3br/Y3YlTwmeFFwkrGfyrV4zPHMnJ03herlAD9sHQcC', NULL, '2026-01-30 04:35:02', '2026-01-30 04:37:17', 'it'),
	(2, 'user1', 'user@gmail.com', NULL, '$2y$12$DwZw6sgHHD9yY21mxfEhu.kwluGmikcx4wwNJHy7eru3DQ/yjeNhu', NULL, '2026-01-30 04:49:06', '2026-01-30 04:49:06', 'user'),
	(3, 'Mewzer', 'mew123@gmail.com', NULL, '$2y$12$5AEBH7c5jJR0Sx1XvVfiUOIgcqaYE9MeTOSTc4E7XADIN0bXzSkfe', NULL, '2026-02-10 04:49:22', '2026-02-10 04:49:22', 'user'),
	(4, 'marta', 'marta@gmail.com', NULL, '$2y$12$Ut9o3AAs1WjvbjoIzH0P1eRRGUjlM9BFqjYcbsq0SaD/lmhvi26gS', NULL, '2026-04-07 06:36:16', '2026-04-07 06:36:16', 'user');

INSERT INTO `tickets` (`id`, `user_id`, `assigned_to`, `full_name`, `class_department`, `category`, `priority`, `title`, `description`, `status`, `created_at`, `updated_at`) VALUES
	(1, 1, NULL, 'Maw', 'Meowzer', 'Software', 'Medium', 'Help', 'Plsplspls', 'open', '2026-01-30 04:44:36', '2026-01-30 04:44:36'),
	(3, 2, 1, 'Mimis', 'Sigma', 'Hardware', 'Low', 'Jager', 'Bombs', 'closed', '2026-01-30 04:53:36', '2026-04-07 07:31:53'),
	(4, 2, NULL, 'Mimis', 'Sigma', 'Printer', 'High', 'Wont print', ':(', 'open', '2026-01-30 04:57:54', '2026-01-30 04:57:54'),
	(5, 3, 1, 'Mimis', 'Sigma', 'Software', 'Low', 'Help', ':(', 'closed', '2026-02-10 04:49:39', '2026-04-07 07:32:52'),
	(6, 4, 1, 'marta', 'sigma', 'Network', 'Medium', 'no wifi :(', 'helpies', 'assigned', '2026-04-07 06:41:18', '2026-04-07 07:02:47'),
	(7, 4, NULL, 'Sigma', 'The most Sigma', 'Printer', 'required', 'No worky', 'help', 'open', '2026-04-07 06:47:37', '2026-04-07 06:47:37');

INSERT INTO `attachments` (`id`, `ticket_id`, `file_path`, `created_at`, `updated_at`) VALUES
	(1, 4, 'tickets/UwBYrFeDtSEepxqRyfpCuN0d0qnU2A3kPl1EqNHg.png', '2026-01-30 04:57:54', '2026-01-30 04:57:54'),
	(2, 6, 'tickets/otjwRYUlN32OgWpdaMBL1eVHZRf7QByH8W8kgUJC.png', '2026-04-07 06:41:19', '2026-04-07 06:41:19'),
	(3, 7, 'tickets/Ttgw6o0ENXDa3kpizouR45iPTJST4tPUnqhusZOA.docx', '2026-04-07 06:47:37', '2026-04-07 06:47:37');

INSERT INTO `comments` (`id`, `ticket_id`, `user_id`, `comment`, `created_at`, `updated_at`) VALUES
	(1, 4, 2, 'hi', '2026-01-30 05:00:26', '2026-01-30 05:00:26'),
	(2, 4, 2, 'yehi', '2026-01-30 05:00:32', '2026-01-30 05:00:32'),
	(3, 6, 4, 'HELP MWEE', '2026-04-07 06:41:38', '2026-04-07 06:41:38'),
	(4, 6, 4, 'no', '2026-04-07 06:58:54', '2026-04-07 06:58:54'),
	(5, 6, 1, 'no', '2026-04-07 07:00:16', '2026-04-07 07:00:16'),
	(6, 3, 1, 'w', '2026-04-07 07:18:50', '2026-04-07 07:18:50');
