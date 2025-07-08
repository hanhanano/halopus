// Kelas untuk fitur pencarian buku
class BookSearch {
    constructor() {
        // Ambil elemen input, form, dan container hasil pencarian
        this.searchInput = document.getElementById("searchInput");
        this.searchResults = document.getElementById("searchResults");
        this.searchForm = document.getElementById("searchForm");
        this.init();
    }

    // Inisialisasi event listener
    init() {
        // Event submit form pencarian
        if (this.searchForm) {
            this.searchForm.addEventListener("submit", (e) => {
                e.preventDefault();
                this.performSearch();
            });
        }

        // Event input realtime saat user mengetik
        if (this.searchInput) {
            let timeout;
            this.searchInput.addEventListener("input", (e) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    if (e.target.value.length >= 3) {
                        this.performLiveSearch(e.target.value);
                    } else {
                        this.clearResults();
                    }
                }, 300);
            });
        }
    }

    // Pencarian buku via AJAX saat submit form
    async performSearch() {
        const query = this.searchInput?.value.trim();
        if (!query) return;

        try {
            const response = await fetch(`pages/cari_buku.php?search=${encodeURIComponent(query)}&ajax=1`);
            const data = await response.json();
            this.displayResults(data);
        } catch (error) {
            console.error("Search error:", error);
        }
    }

    // Pencarian live saat user mengetik (minimal 3 karakter)
    async performLiveSearch(query) {
        try {
            const response = await fetch(`pages/cari_buku.php?search=${encodeURIComponent(query)}&ajax=1&live=1`);
            const data = await response.json();
            this.displayLiveResults(data);
        } catch (error) {
            console.error("Live search error:", error);
        }
    }

    // Tampilkan hasil pencarian utama di halaman
    displayResults(data) {
        if (!this.searchResults) return;

        if (data.length === 0) {
            this.searchResults.innerHTML = '<p class="text-center">Tidak ada buku yang ditemukan.</p>';
            return;
        }

        let html = '<div class="books-grid">';
        data.forEach((book) => {
            html += this.createBookCard(book);
        });
        html += "</div>";

        this.searchResults.innerHTML = html;
    }

    // Tampilkan hasil live search dalam dropdown
    displayLiveResults(data) {
        let dropdown = document.getElementById("searchDropdown");
        if (!dropdown) {
            dropdown = document.createElement("div");
            dropdown.id = "searchDropdown";
            dropdown.className = "search-dropdown";
            this.searchInput.parentNode.appendChild(dropdown);
        }

        if (data.length === 0) {
            dropdown.style.display = "none";
            return;
        }

        let html = "";
        data.slice(0, 5).forEach((book) => {
            html += `
        <div class="search-item" onclick="selectBook('${book.id}')">
          <strong>${book.title}</strong>
          <small>oleh ${book.author}</small>
        </div>
      `;
        });

        dropdown.innerHTML = html;
        dropdown.style.display = "block";
    }

    // Template HTML kartu buku
    createBookCard(book) {
        return `
      <div class="book-card">
        <div class="book-cover">
          ${book.title}
        </div>
        <div class="book-info">
          <h3 class="book-title">${book.title}</h3>
          <p class="book-author">oleh ${book.author}</p>
          <p class="book-price">${this.formatCurrency(book.price)}</p>
          <div class="book-actions">
            <a href="pages/detail_buku.php?id=${book.id}" class="btn btn-primary btn-sm">Detail</a>
            <a href="pages/edit_buku.php?id=${book.id}" class="btn btn-warning btn-sm">Edit</a>
          </div>
        </div>
      </div>
    `;
    }

    // Format angka ke mata uang Rupiah
    formatCurrency(amount) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0
        }).format(amount);
    }

    // Hapus hasil pencarian & dropdown live search
    clearResults() {
        if (this.searchResults) {
            this.searchResults.innerHTML = "";
        }

        const dropdown = document.getElementById("searchDropdown");
        if (dropdown) {
            dropdown.style.display = "none";
        }
    }
}

// CSS untuk dropdown hasil live search
const searchDropdownStyles = `
  .search-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    z-index: 1000;
    max-height: 300px;
    overflow-y: auto;
  }
  .search-item {
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s ease;
  }
  .search-item:hover {
    background-color: #f8f9fa;
  }
  .search-item:last-child {
    border-bottom: none;
  }
  .search-item strong {
    display: block;
    color: #333;
  }
  .search-item small {
    color: #666;
    font-size: 0.875rem;
  }
`;

// Tambahkan style ke <head>
const styleSheet = document.createElement("style");
styleSheet.textContent = searchDropdownStyles;
document.head.appendChild(styleSheet);

// Jalankan BookSearch setelah DOM siap
document.addEventListener("DOMContentLoaded", () => {
    const bookSearch = new BookSearch();
});

// Fungsi untuk redirect ke detail buku saat item live search diklik
function selectBook(bookId) {
    window.location.href = `pages/detail_buku.php?id=${bookId}`;
}