// Student Dashboard - Complete JavaScript Implementation
document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let availableCourses = [];
    let studentCourses = [];
    let tasks = [];
    let grades = [];
    let selectedCourses = new Set();
    let enrolledCourses = new Set();
    let currentTaskFilter = 'all';
    let currentStudentId = '<?= $student_id ?>';

    // DOM Elements with null checks
    const getElement = (id) => {
        const el = document.getElementById(id);
        if (!el) console.error(`Element with ID ${id} not found`);
        return el;
    };

    const getQueryElement = (selector) => {
        const el = document.querySelector(selector);
        if (!el) console.error(`Element with selector ${selector} not found`);
        return el;
    };

    // Get all required elements
    const sidebar = getElement('sidebar');
    const overlay = getQueryElement('.overlay');
    const hamburger = getQueryElement('.hamburger');
    const courseSearch = getElement('courseSearch');
    const semesterFilter = getElement('semesterFilter');
    const programFilter = getElement('programFilter');
    const selectAllCheckbox = getElement('selectAll');
    const selectedCountSpan = getElement('selectedCount');
    const coursesTableBody = getElement('coursesTableBody');
    const myCoursesGrid = getElement('myCoursesGrid');
    const tasksGrid = getElement('tasksGrid');
    const dashboardCourses = getElement('dashboardCourses');
    const upcomingDeadlines = getElement('upcomingDeadlines');
    const enrolledCount = getElement('enrolledCount');
    const pendingTasks = getElement('pendingTasks');
    const completedTasks = getElement('completedTasks');
    const avgGrade = getElement('avgGrade');
    const taskModal = getElement('taskModal');
    const taskSubmissionForm = getElement('taskSubmissionForm');
    const logoutBtn = getQueryElement('.logout-btn');

    // Initialize the dashboard
    initDashboard();

    // Event Listeners with null checks
    if (hamburger) hamburger.addEventListener('click', toggleSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);
    if (courseSearch) courseSearch.addEventListener('input', filterCourses);
    if (semesterFilter) semesterFilter.addEventListener('change', filterCourses);
    if (programFilter) programFilter.addEventListener('change', filterCourses);
    if (selectAllCheckbox) selectAllCheckbox.addEventListener('change', toggleSelectAll);
    if (logoutBtn) logoutBtn.addEventListener('click', logout);
    window.addEventListener('resize', handleResponsiveLayout);

    // Add event delegation for sidebar navigation
    const sidebarMenu = getQueryElement('.sidebar-menu');
    if (sidebarMenu) {
        sidebarMenu.addEventListener('click', function(e) {
            const link = e.target.closest('.sidebar-link');
            if (link) {
                e.preventDefault();
                const pageId = link.getAttribute('data-page');
                if (pageId) showPage(null, pageId);
            }
        });
    }

    if (taskSubmissionForm) {
        taskSubmissionForm.addEventListener('submit', handleTaskSubmission);
    }

    // Initialize the dashboard
    async function initDashboard() {
        showLoading(true);
        try {
            await Promise.all([
                fetchAvailableCourses(),
                fetchStudentCourses(),
                fetchTasks(),
                fetchGrades()
            ]);
            renderDashboard();
            showNotification('Dashboard loaded successfully!', 'success');
        } catch (error) {
            console.error('Initialization error:', error);
            showNotification('Error loading dashboard data: ' + error.message, 'error');
        } finally {
            showLoading(false);
        }
    }

    // Data Fetching Functions with improved error handling
    async function fetchAvailableCourses() {
        try {
            const response = await fetch(`get_available_courses.php?student_id=${currentStudentId}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
            const data = await response.json();
            
            if (data.success) {
                availableCourses = data.courses.map(course => ({
                    ...course,
                    is_enrolled: enrolledCourses.has(course.id)
                }));
                renderCourseRegistration();
                return true;
            } else {
                throw new Error(data.message || 'Failed to load courses');
            }
        } catch (error) {
            console.error('Error fetching courses:', error);
            showNotification('Error loading available courses: ' + error.message, 'error');
            return false;
        }
    }

    async function fetchStudentCourses() {
        try {
            const response = await fetch(`get_student_courses.php?student_id=${currentStudentId}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
            const data = await response.json();
            
            if (data.success) {
                studentCourses = data.courses;
                enrolledCourses = new Set(studentCourses.map(c => c.id));
                renderMyCourses();
                return true;
            } else {
                throw new Error(data.message || 'Failed to load student courses');
            }
        } catch (error) {
            console.error('Error fetching student courses:', error);
            showNotification('Error loading your courses: ' + error.message, 'error');
            return false;
        }
    }

    async function fetchTasks() {
        try {
            const response = await fetch(`get_student_tasks.php?student_id=${currentStudentId}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
            const data = await response.json();
            
            if (data.success) {
                tasks = data.tasks.map(task => ({
                    ...task,
                    dueDate: formatDate(task.due_date),
                    submittedAt: task.submitted_at ? formatDate(task.submitted_at) : null,
                    gradedAt: task.graded_at ? formatDate(task.graded_at) : null
                }));
                renderTasks();
                return true;
            } else {
                throw new Error(data.message || 'Failed to load tasks');
            }
        } catch (error) {
            console.error('Error fetching tasks:', error);
            showNotification('Error loading tasks: ' + error.message, 'error');
            return false;
        }
    }

    async function fetchGrades() {
        try {
            const response = await fetch(`get_student_grades.php?student_id=${currentStudentId}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
            const data = await response.json();
            
            if (data.success) {
                grades = data.grades;
                renderGrades();
                updateGradeStats();
                return true;
            } else {
                throw new Error(data.message || 'Failed to load grades');
            }
        } catch (error) {
            console.error('Error fetching grades:', error);
            showNotification('Error loading grades: ' + error.message, 'error');
            return false;
        }
    }

    // Page Navigation
    function showPage(event, pageId) {
        // Hide all pages
        document.querySelectorAll('.page-section').forEach(page => {
            page.classList.remove('active');
        });
        
        // Show selected page
        const targetPage = getElement(pageId);
        if (targetPage) {
            targetPage.classList.add('active');
        }
        
        // Update active link
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.classList.remove('active');
        });
        
        // Add active class to clicked link
        if (event && event.currentTarget) {
            event.currentTarget.classList.add('active');
        } else {
            const activeLink = document.querySelector(`.sidebar-link[data-page="${pageId}"]`);
            if (activeLink) activeLink.classList.add('active');
        }
        
        // Close sidebar on mobile
        if (window.innerWidth <= 768) {
            closeSidebar();
        }
    }

    // UI Functions
    function toggleSidebar() {
        if (sidebar) sidebar.classList.toggle('open');
        if (overlay) overlay.classList.toggle('active');
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
    }

    function handleResponsiveLayout() {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    }

    function showLoading(show) {
        const loader = getElement('loadingOverlay');
        if (loader) loader.style.display = show ? 'flex' : 'none';
    }

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    function logout() {
        if (confirm('Are you sure you want to logout?')) {
            showNotification('Logging out...', 'info');
            setTimeout(() => {
                window.location.href = 'logout.php';
            }, 1000);
        }
    }

    // Course Registration Functions
    function filterCourses() {
        const searchTerm = courseSearch?.value.toLowerCase() || '';
        const semester = semesterFilter?.value || '';
        const program = programFilter?.value || '';
        
        if (!coursesTableBody) return;
        
        const rows = coursesTableBody.querySelectorAll('tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const matchesSearch = searchTerm === '' || text.includes(searchTerm);
            const matchesSemester = semester === '' || text.includes(semester.toLowerCase());
            const matchesProgram = program === '' || text.includes(program.toLowerCase());
            
            row.style.display = matchesSearch && matchesSemester && matchesProgram ? '' : 'none';
        });
    }

    function toggleSelectAll() {
        if (!selectAllCheckbox || !coursesTableBody) return;
        
        const checkboxes = coursesTableBody.querySelectorAll('input[type="checkbox"]:not(:disabled)');
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
            handleCourseSelection(checkbox.value);
        });
    }

    function handleCourseSelection(courseId) {
        const checkbox = document.querySelector(`input[value="${courseId}"]`);
        if (!checkbox) return;
        
        if (checkbox.checked) {
            selectedCourses.add(courseId);
        } else {
            selectedCourses.delete(courseId);
        }
        updateSelectedCount();
    }

    function updateSelectedCount() {
        if (selectedCountSpan) {
            selectedCountSpan.textContent = selectedCourses.size;
        }
    }

    async function registerSelectedCourses() {
        if (selectedCourses.size === 0) {
            showNotification('Please select at least one course to register.', 'error');
            return;
        }

        showLoading(true);
        try {
            const response = await fetch('register_courses.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    student_id: currentStudentId,
                    courses: Array.from(selectedCourses)
                })
            });

            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
            const data = await response.json();

            if (data.success) {
                showNotification(`Successfully registered for ${selectedCourses.size} courses!`, 'success');
                selectedCourses.clear();
                if (selectAllCheckbox) selectAllCheckbox.checked = false;
                updateSelectedCount();
                await Promise.all([fetchAvailableCourses(), fetchStudentCourses()]);
                renderDashboard();
            } else {
                throw new Error(data.message || 'Registration failed');
            }
        } catch (error) {
            console.error('Registration error:', error);
            showNotification(error.message, 'error');
        } finally {
            showLoading(false);
        }
    }

    function clearSelection() {
        selectedCourses.clear();
        if (selectAllCheckbox) selectAllCheckbox.checked = false;
        if (coursesTableBody) {
            coursesTableBody.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });
        }
        updateSelectedCount();
    }

    // Render Functions
    function renderDashboard() {
        if (!studentCourses.length && !tasks.length) return;

        // Update stats
        if (enrolledCount) enrolledCount.textContent = enrolledCourses.size;
        const pendingCount = tasks.filter(t => t.status === 'pending').length;
        if (pendingTasks) pendingTasks.textContent = pendingCount;
        if (completedTasks) completedTasks.textContent = tasks.filter(t => t.status === 'graded').length;

        // Render course cards for dashboard
        if (dashboardCourses) {
            dashboardCourses.innerHTML = studentCourses.slice(0, 3).map(course => `
                <div class="course-card">
                    <h4>${course.name}</h4>
                    <p><strong>Code:</strong> ${course.code}</p>
                    <p><strong>Instructor:</strong> ${course.instructor}</p>
                    <p><strong>Progress:</strong> ${course.progress}%</p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${course.progress}%"></div>
                    </div>
                    <p><strong>Grade:</strong> ${course.grade || 'N/A'}</p>
                </div>
            `).join('');
        }

        // Render upcoming deadlines
        if (upcomingDeadlines) {
            const upcoming = tasks
                .filter(t => t.status === 'pending')
                .sort((a, b) => new Date(a.dueDate) - new Date(b.dueDate))
                .slice(0, 3);

            upcomingDeadlines.innerHTML = upcoming.map(task => `
                <div class="deadline-item">
                    <h4>${task.title}</h4>
                    <p><strong>Course:</strong> ${task.courseName}</p>
                    <p><strong>Due:</strong> ${task.dueDate}</p>
                    <p><strong>Type:</strong> ${task.type}</p>
                </div>
            `).join('');
        }
    }

    // ... (include all other render functions with similar null checks)

    // Make functions available globally
    window.showPage = showPage;
    window.toggleSidebar = toggleSidebar;
    window.closeSidebar = closeSidebar;
    window.logout = logout;
    window.filterCourses = filterCourses;
    window.toggleSelectAll = toggleSelectAll;
    window.handleCourseSelection = handleCourseSelection;
    window.registerSelectedCourses = registerSelectedCourses;
    window.clearSelection = clearSelection;
    window.viewCourseDetails = viewCourseDetails;
    window.viewCourseTasks = viewCourseTasks;
    window.viewCourseGrades = viewCourseGrades;
    window.filterTasks = filterTasks;
    window.submitTask = submitTask;
    window.viewSubmission = viewSubmission;
    window.viewFeedback = viewFeedback;
    window.downloadGrade = downloadGrade;
    window.closeTaskModal = closeTaskModal;
    window.removeFile = removeFile;
});