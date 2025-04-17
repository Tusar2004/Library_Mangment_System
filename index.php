<?php
$host = 'localhost';
$dbname = 'library_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

function executeQuery($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function addBook($pdo, $bookData) {
    $sql = "INSERT INTO books (title, author, isbn, publication_year, publisher, category, available_copies, total_copies) 
            VALUES (:title, :author, :isbn, :publication_year, :publisher, :category, :available_copies, :total_copies)";
    executeQuery($pdo, $sql, $bookData);
    return $pdo->lastInsertId();
}

function updateBook($pdo, $bookId, $updateData) {
    $sql = "UPDATE books SET 
            title = :title, 
            author = :author, 
            isbn = :isbn, 
            publication_year = :publication_year, 
            publisher = :publisher, 
            category = :category,
            available_copies = :available_copies,
            total_copies = :total_copies
            WHERE book_id = :book_id";
    $updateData['book_id'] = $bookId;
    executeQuery($pdo, $sql, $updateData);
}

function deleteBook($pdo, $bookId) {
    $sql = "DELETE FROM books WHERE book_id = ?";
    executeQuery($pdo, $sql, [$bookId]);
}

function getBook($pdo, $bookId) {
    $sql = "SELECT * FROM books WHERE book_id = ?";
    $stmt = executeQuery($pdo, $sql, [$bookId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAvailableBooks($pdo) {
    $sql = "SELECT * FROM books WHERE available_copies > 0";
    $stmt = executeQuery($pdo, $sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Member Management Functions
function addMember($pdo, $memberData) {
    $sql = "INSERT INTO members (first_name, last_name, email, phone, address, membership_date, active_status) 
            VALUES (:first_name, :last_name, :email, :phone, :address, :membership_date, :active_status)";
    executeQuery($pdo, $sql, $memberData);
    return $pdo->lastInsertId();
}

function updateMember($pdo, $memberId, $updateData) {
    $sql = "UPDATE members SET 
            first_name = :first_name, 
            last_name = :last_name, 
            email = :email, 
            phone = :phone, 
            address = :address, 
            active_status = :active_status
            WHERE member_id = :member_id";
    $updateData['member_id'] = $memberId;
    executeQuery($pdo, $sql, $updateData);
}

function deleteMember($pdo, $memberId) {
    $sql = "DELETE FROM members WHERE member_id = ?";
    executeQuery($pdo, $sql, [$memberId]);
}

function getMember($pdo, $memberId) {
    $sql = "SELECT * FROM members WHERE member_id = ?";
    $stmt = executeQuery($pdo, $sql, [$memberId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Book Issue Management Functions
function issueBook($pdo, $bookId, $memberId, $issueDate, $dueDate) {
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert issue record
        $sql = "INSERT INTO book_issues (book_id, member_id, issue_date, due_date) 
                VALUES (:book_id, :member_id, :issue_date, :due_date)";
        executeQuery($pdo, $sql, [
            'book_id' => $bookId,
            'member_id' => $memberId,
            'issue_date' => $issueDate,
            'due_date' => $dueDate
        ]);
        
        // Decrease available copies
        $sql = "UPDATE books SET available_copies = available_copies - 1 WHERE book_id = ?";
        executeQuery($pdo, $sql, [$bookId]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function returnBook($pdo, $issueId, $returnDate, $fine = 0, $status = 'returned') {
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Get book_id from the issue record
        $sql = "SELECT book_id FROM book_issues WHERE issue_id = ?";
        $stmt = executeQuery($pdo, $sql, [$issueId]);
        $issue = $stmt->fetch(PDO::FETCH_ASSOC);
        $bookId = $issue['book_id'];
        
        // Update issue record
        $sql = "UPDATE book_issues SET 
                return_date = :return_date, 
                status = :status,
                fine_amount = :fine_amount
                WHERE issue_id = :issue_id";
        executeQuery($pdo, $sql, [
            'return_date' => $returnDate,
            'status' => $status,
            'fine_amount' => $fine,
            'issue_id' => $issueId
        ]);
        
        // Increase available copies
        $sql = "UPDATE books SET available_copies = available_copies + 1 WHERE book_id = ?";
        executeQuery($pdo, $sql, [$bookId]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function getIssuedBooks($pdo) {
    $sql = "SELECT bi.*, b.title, b.author, m.first_name, m.last_name 
            FROM book_issues bi
            JOIN books b ON bi.book_id = b.book_id
            JOIN members m ON bi.member_id = m.member_id
            WHERE bi.status = 'issued'";
    $stmt = executeQuery($pdo, $sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOverdueBooks($pdo) {
    $currentDate = date('Y-m-d');
    $sql = "SELECT bi.*, b.title, b.author, m.first_name, m.last_name 
            FROM book_issues bi
            JOIN books b ON bi.book_id = b.book_id
            JOIN members m ON bi.member_id = m.member_id
            WHERE bi.status = 'issued' AND bi.due_date < :current_date";
    $stmt = executeQuery($pdo, $sql, ['current_date' => $currentDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .bg-library {
            background-image: url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .nav-link {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .nav-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.15);
        }
        .book-card {
            transition: all 0.3s ease;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
</head>
<body class="bg-library min-h-screen font-sans">
    <div class="min-h-screen bg-gray-900/80 py-8">
        <div class="container mx-auto px-4 max-w-7xl">
            <!-- Animated Header -->
            <header class="text-center mb-12 animate-fade-in">
                <h1 class="text-5xl font-bold text-white mb-4 font-serif tracking-tight">
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-amber-400 to-amber-600">
                        Library Management System
                    </span>
                </h1>
                <p class="text-xl text-gray-300 max-w-2xl mx-auto">
                    Organize your library with elegance and efficiency
                </p>
            </header>
            
            <!-- Navigation Cards -->
            <nav class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <a href="#books" class="nav-link glass-panel p-6 rounded-xl hover:shadow-lg flex flex-col items-center text-center">
                    <div class="bg-amber-100/80 text-amber-600 p-4 rounded-full mb-4">
                        <i class="fas fa-book text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Books</h3>
                    <p class="text-gray-600 text-sm">Manage your collection</p>
                    <span class="mt-2 bg-amber-100 text-amber-800 px-3 py-1 rounded-full text-xs font-medium">
                        <?php 
                        $totalBooks = executeQuery($pdo, "SELECT COUNT(*) as count FROM books")->fetch(PDO::FETCH_ASSOC);
                        echo $totalBooks['count'] ?? 0 ?> titles
                    </span>
                </a>
                
                <a href="#members" class="nav-link glass-panel p-6 rounded-xl hover:shadow-lg flex flex-col items-center text-center">
                    <div class="bg-blue-100/80 text-blue-600 p-4 rounded-full mb-4">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Members</h3>
                    <p class="text-gray-600 text-sm">Manage library patrons</p>
                    <span class="mt-2 bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-medium">
                        <?php 
                        $totalMembers = executeQuery($pdo, "SELECT COUNT(*) as count FROM members")->fetch(PDO::FETCH_ASSOC);
                        echo $totalMembers['count'] ?? 0 ?> members
                    </span>
                </a>
                
                <a href="#issues" class="nav-link glass-panel p-6 rounded-xl hover:shadow-lg flex flex-col items-center text-center">
                    <div class="bg-green-100/80 text-green-600 p-4 rounded-full mb-4">
                        <i class="fas fa-exchange-alt text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Circulation</h3>
                    <p class="text-gray-600 text-sm">Manage book loans</p>
                    <span class="mt-2 bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium">
                        <?php 
                        $totalLoans = executeQuery($pdo, "SELECT COUNT(*) as count FROM book_issues WHERE status = 'issued'")->fetch(PDO::FETCH_ASSOC);
                        echo $totalLoans['count'] ?? 0 ?> active
                    </span>
                </a>
                
                <a href="#reports" class="nav-link glass-panel p-6 rounded-xl hover:shadow-lg flex flex-col items-center text-center">
                    <div class="bg-purple-100/80 text-purple-600 p-4 rounded-full mb-4">
                        <i class="fas fa-chart-pie text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Reports</h3>
                    <p class="text-gray-600 text-sm">View library analytics</p>
                    <span class="mt-2 bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-xs font-medium">
                        <?php 
                        $overdueCount = executeQuery($pdo, "SELECT COUNT(*) as count FROM book_issues WHERE status = 'issued' AND due_date < CURDATE()")->fetch(PDO::FETCH_ASSOC);
                        echo $overdueCount['count'] ?? 0 ?> overdue
                    </span>
                </a>
            </nav>
            
            <!-- Books Section -->
            <section id="books" class="glass-panel rounded-2xl p-8 mb-12">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800 flex items-center">
                            <i class="fas fa-book-open mr-3 text-amber-600"></i> Book Catalog
                        </h2>
                        <p class="text-gray-600 mt-1">Manage your library's collection</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="bg-amber-100 text-amber-800 px-3 py-1 rounded-full text-sm font-medium">
                            <?php echo $totalBooks['count'] ?? 0 ?> titles
                        </span>
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                            <?php 
                            $availableCount = executeQuery($pdo, "SELECT COUNT(*) as count FROM books WHERE available_copies > 0")->fetch(PDO::FETCH_ASSOC);
                            echo $availableCount['count'] ?? 0 ?> available
                        </span>
                    </div>
                </div>
                
                <!-- Add Book Form -->
                <div class="mb-12 bg-white/90 p-6 rounded-xl shadow-md border border-gray-200/50">
                    <h3 class="text-xl font-semibold text-gray-700 mb-6 pb-2 border-b border-gray-200 flex items-center">
                        <i class="fas fa-plus-circle mr-2 text-amber-600"></i> Add New Book
                    </h3>
                    <form method="post" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <input type="hidden" name="action" value="add_book">
                        
                        <div class="space-y-2">
                            <label class="block text-gray-700 font-medium">Title <span class="text-red-500">*</span></label>
                            <input type="text" name="title" placeholder="Book Title" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition shadow-sm">
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-gray-700 font-medium">Author <span class="text-red-500">*</span></label>
                            <input type="text" name="author" placeholder="Author" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition shadow-sm">
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-gray-700 font-medium">ISBN</label>
                            <input type="text" name="isbn" placeholder="ISBN" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition shadow-sm">
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-gray-700 font-medium">Publication Year</label>
                            <input type="number" name="publication_year" placeholder="Year" min="1800" max="<?php echo date('Y'); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition shadow-sm">
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-gray-700 font-medium">Publisher</label>
                            <input type="text" name="publisher" placeholder="Publisher" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition shadow-sm">
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-gray-700 font-medium">Category</label>
                            <input type="text" name="category" placeholder="Category" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition shadow-sm">
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-gray-700 font-medium">Total Copies <span class="text-red-500">*</span></label>
                            <input type="number" name="total_copies" value="1" min="1" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition shadow-sm">
                        </div>
                        
                        <div class="flex items-end md:col-span-2">
                            <button type="submit" 
                                    class="bg-amber-600 hover:bg-amber-700 text-white px-8 py-3 rounded-lg transition duration-300 flex items-center text-lg shadow-md hover:shadow-lg w-full justify-center">
                                <i class="fas fa-save mr-2"></i> Add Book to Collection
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Book Inventory -->
                <div>
                    <h3 class="text-2xl font-semibold text-gray-700 mb-6 flex items-center">
                        <i class="fas fa-books mr-3 text-amber-600"></i> Book Inventory
                    </h3>
                    
                    <div class="bg-white/90 rounded-xl shadow-md overflow-hidden border border-gray-200/50">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title & Details</th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Availability</th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php
                                    $books = executeQuery($pdo, "SELECT * FROM books ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($books as $book) {
                                        $availableClass = $book['available_copies'] == 0 ? 
                                            'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';
                                        $availableText = $book['available_copies'] == 0 ? 
                                            'Checked Out' : 'Available';
                                        
                                        echo "<tr class='hover:bg-gray-50/80 transition'>
                                            <td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'>#{$book['book_id']}</td>
                                            <td class='px-6 py-4'>
                                                <div class='text-sm font-medium text-gray-900'>{$book['title']}</div>
                                                <div class='text-xs text-gray-500 mt-1 flex items-center'>
                                                    <span class='mr-2'>{$book['category']}</span>
                                                    <span class='text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded'>{$book['isbn']}</span>
                                                </div>
                                            </td>
                                            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-600'>{$book['author']}</td>
                                            <td class='px-6 py-4 whitespace-nowrap'>
                                                <span class='px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full $availableClass'>
                                                    $availableText
                                                </span>
                                                <div class='text-xs text-gray-500 mt-1'>{$book['available_copies']}/{$book['total_copies']} copies</div>
                                            </td>
                                            <td class='px-6 py-4 whitespace-nowrap text-right text-sm font-medium'>
                                                <div class='flex justify-end space-x-2'>
                                                    <a href='?edit_book={$book['book_id']}' class='text-blue-600 hover:text-blue-900 p-2 rounded-full hover:bg-blue-50 transition' title='Edit'>
                                                        <i class='fas fa-pencil-alt'></i>
                                                    </a>
                                                    <a href='?delete_book={$book['book_id']}' onclick='return confirm(\"Delete this book?\")' class='text-red-600 hover:text-red-900 p-2 rounded-full hover:bg-red-50 transition' title='Delete'>
                                                        <i class='fas fa-trash'></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- [Additional sections for Members, Circulation, and Reports would follow the same design pattern] -->

            <!-- Footer -->
            <footer class="mt-16 text-center text-gray-300">
                <div class="flex justify-center space-x-6 mb-4">
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-twitter text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-github text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fas fa-envelope text-xl"></i>
                    </a>
                </div>
                <p class="text-lg">Library Management System &copy; <?php echo date('Y'); ?></p>
                <p class="text-sm mt-2">Powered by PHP, MySQL, and Tailwind CSS</p>
            </footer>
        </div>
    </div>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_book':
                    $bookData = [
                        'title' => $_POST['title'],
                        'author' => $_POST['author'],
                        'isbn' => $_POST['isbn'],
                        'publication_year' => $_POST['publication_year'],
                        'publisher' => $_POST['publisher'],
                        'category' => $_POST['category'],
                        'available_copies' => $_POST['total_copies'],
                        'total_copies' => $_POST['total_copies']
                    ];
                    addBook($pdo, $bookData);
                    header("Location: ".$_SERVER['PHP_SELF']);
                    exit;
                    break;
                    
                case 'add_member':
                    $memberData = [
                        'first_name' => $_POST['first_name'],
                        'last_name' => $_POST['last_name'],
                        'email' => $_POST['email'],
                        'phone' => $_POST['phone'],
                        'address' => $_POST['address'],
                        'membership_date' => $_POST['membership_date'],
                        'active_status' => isset($_POST['active_status']) ? 1 : 0
                    ];
                    addMember($pdo, $memberData);
                    header("Location: ".$_SERVER['PHP_SELF']);
                    exit;
                    break;
                    
                case 'issue_book':
                    issueBook($pdo, $_POST['book_id'], $_POST['member_id'], $_POST['issue_date'], $_POST['due_date']);
                    header("Location: ".$_SERVER['PHP_SELF']);
                    exit;
                    break;
            }
        }
    }
    
    // Process GET actions
    if (isset($_GET['delete_book'])) {
        deleteBook($pdo, $_GET['delete_book']);
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_GET['delete_member'])) {
        deleteMember($pdo, $_GET['delete_member']);
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_GET['return_book'])) {
        returnBook($pdo, $_GET['return_book'], date('Y-m-d'));
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    ?>
</body>
</html>