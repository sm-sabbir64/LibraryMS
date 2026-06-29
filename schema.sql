-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS library_ms;
USE library_ms;

-- Admins Table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books Table
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    cover_url VARCHAR(255),
    author VARCHAR(255) NOT NULL,
    isbn VARCHAR(50) UNIQUE,
    published_year INT,
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Borrowers Table
CREATE TABLE IF NOT EXISTS borrowers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Transactions Table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    borrower_id INT NOT NULL,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    status ENUM('borrowed', 'returned') DEFAULT 'borrowed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (borrower_id) REFERENCES borrowers(id) ON DELETE CASCADE
);

-- Insert Sample Data
INSERT INTO books (title, author, isbn, description, category, cover_url, published_year, total_copies, available_copies) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', '9780743273565', 'A classic novel exploring themes of decadence, idealism, and resistance to change.', 'Classic Fiction', 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?q=80&w=600&auto=format&fit=crop', 1925, 5, 5),
('To Kill a Mockingbird', 'Harper Lee', '9780060935467', 'A novel about the serious issues of rape and racial inequality told from a child''s perspective.', 'Historical Fiction', 'https://images.unsplash.com/photo-1589829085413-56de8ae18c73?q=80&w=600&auto=format&fit=crop', 1960, 3, 3),
('1984', 'George Orwell', '9780451524935', 'A dystopian social science fiction novel and cautionary tale about the dangers of totalitarianism.', 'Science Fiction', 'https://images.unsplash.com/photo-1512820790803-83ca734da794?q=80&w=600&auto=format&fit=crop', 1949, 4, 4),
('Pride and Prejudice', 'Jane Austen', '9780141439518', 'A romantic novel of manners that follows the character development of Elizabeth Bennet.', 'Romance', 'https://images.unsplash.com/photo-1589998059171-989d887dda6e?q=80&w=600&auto=format&fit=crop', 1813, 2, 2),
('Clean Code', 'Robert C. Martin', '9780132350884', 'A Handbook of Agile Software Craftsmanship.', 'Programming', 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?q=80&w=600&auto=format&fit=crop', 2008, 4, 4),
('The Pragmatic Programmer', 'Andrew Hunt', '9780135957059', 'Your journey to mastery, 20th Anniversary Edition.', 'Programming', 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=600&auto=format&fit=crop', 1999, 3, 3),
('Thinking, Fast and Slow', 'Daniel Kahneman', '9780374533557', 'A deep dive into the two systems that drive the way we think.', 'Psychology', 'https://images.unsplash.com/photo-1550592704-6c76defa99ce?q=80&w=600&auto=format&fit=crop', 2011, 5, 5),
('Atomic Habits', 'James Clear', '9780735211292', 'An Easy & Proven Way to Build Good Habits & Break Bad Ones.', 'Self-Help', 'https://images.unsplash.com/photo-1589829085413-56de8ae18c73?q=80&w=600&auto=format&fit=crop', 2018, 6, 6),
('Sapiens', 'Yuval Noah Harari', '9780062316097', 'A Brief History of Humankind.', 'History', 'https://images.unsplash.com/photo-1461360370896-922624d12aa1?q=80&w=600&auto=format&fit=crop', 2015, 4, 4),
('Zero to One', 'Peter Thiel', '9780804139298', 'Notes on Startups, or How to Build the Future.', 'Business', 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?q=80&w=600&auto=format&fit=crop', 2014, 3, 3)
ON DUPLICATE KEY UPDATE title=title;

-- Insert Default Admin (Password is '12345' hashed)
INSERT INTO admins (name, email, password) VALUES
('System Admin', 'admin@example.com', '$2y$10$eE08N9WcR44M.mNofbFfK.VvR.4D59wW8W6K64T/mO07Z5.x3fJc2')
ON DUPLICATE KEY UPDATE name=name;
