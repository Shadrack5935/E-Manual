let instructors = [
    { id: 'INS001', name: 'Dr. John Smith' },
    { id: 'INS002', name: 'Prof. Jane Johnson' },
    { id: 'INS003', name: 'Dr. Michael Brown' },
    { id: 'INS004', name: 'Prof. Sarah Davis' },
    { id: 'INS005', name: 'Dr. Robert Wilson' },
    { id: 'INS006', name: 'Prof. Emily Chen' }
];

let currentCourse = null;
let editingCourse = null;

// Helper function to get instructor by ID
function getInstructorById(id) {
    return instructors.find(instructor => instructor.id === id);
}

// Fixed function to get instructor names for display
function getInstructorNames(instructorIds) {
    if (!instructorIds || instructorIds.length === 0) {
        return [];
    }
    return instructorIds.map(id => {
        const instructor = getInstructorById(id);
        return instructor ? instructor.name : 'Unknown';
    });
}

// Helper function to get initials
function getInitials(name) {
    return name.split(' ').map(word => word.charAt(0)).join('').toUpperCase();
}

// Generate unique course ID
function generateCourseId() {
    const maxId = courses.reduce((max, course) => {
        const num = parseInt(course.id.replace('CRS', ''));
        return num > max ? num : max;
    }, 0);
    return `CRS${String(maxId + 1).padStart(3, '0')}`;
}

// Fixed render courses function
function renderCourses() {
    const tbody = document.getElementById('coursesTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = courses.map(course => `
        <tr>
            <td><strong>${course.code}</strong></td>
            <td>${course.name}</td>
            <td>${course.credits}</td>
            <td><span class="program-badge">${course.program}</span></td>
            <td>${course.semester}</td>
            <td>${course.maxStudents}</td>
            <td>
                <div class="instructor-list">
                    ${getInstructorNames(course.instructorIds).map(name => 
                        `<span class="instructor-badge">${name}</span>`
                    ).join('')}
                    ${(!course.instructorIds || course.instructorIds.length === 0) ? '<span class="instructor-badge" style="background: #ffebee; color: #c62828;">Unassigned</span>' : ''}
                </div>
            </td>
            <td><span class="course-status ${course.status === 'active' ? 'status-active' : 'status-inactive'}">${course.status.charAt(0).toUpperCase() + course.status.slice(1)}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-small btn-edit" onclick="editCourse('${course.id}')">Edit</button>
                    <button class="btn btn-small btn-delete" onclick="deleteCourse('${course.id}')">Delete</button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Fixed update statistics function
function updateStats() {
    const totalCourses = courses.length;
    const activeCourses = courses.filter(c => c.status === 'active').length;
    const assignedCourses = courses.filter(c => c.instructorIds && c.instructorIds.length > 0).length;
    const totalPrograms = new Set(courses.map(c => c.program)).size;
    
    const totalCoursesEl = document.getElementById('totalCourses');
    const activeCoursesEl = document.getElementById('activeCourses');
    const assignedCoursesEl = document.getElementById('assignedCourses');
    const totalProgramsEl = document.getElementById('totalPrograms');
    
    if (totalCoursesEl) totalCoursesEl.textContent = totalCourses;
    if (activeCoursesEl) activeCoursesEl.textContent = activeCourses;
    if (assignedCoursesEl) assignedCoursesEl.textContent = assignedCourses;
    if (totalProgramsEl) totalProgramsEl.textContent = totalPrograms;
}

// Fixed course form submission
function handleCourseFormSubmit(e) {
    e.preventDefault();
    
    const form = document.getElementById('addCourseForm');
    if (!form) {
        console.error('Course form not found');
        return;
    }
    
    const formData = new FormData(form);
    
    const courseData = {
        id: editingCourse ? editingCourse.id : generateCourseId(),
        code: formData.get('courseCode'),
        name: formData.get('courseName'),
        credits: parseInt(formData.get('credits')),
        program: formData.get('program'),
        semester: formData.get('semester'),
        maxStudents: parseInt(formData.get('maxStudents')),
        description: formData.get('description'),
        instructorId: formData.get('instructor') || '',
        instructorIds: formData.get('instructor') ? [formData.get('instructor')] : [],
        status: 'active'
    };
    
    if (editingCourse) {
        const index = courses.findIndex(c => c.id === editingCourse.id);
        if (index !== -1) {
            courses[index] = courseData;
        }
        editingCourse = null;
    } else {
        courses.push(courseData);
    }
    
    renderCourses();
    updateStats();
    resetCourseForm();
}

function resetCourseForm() {
    const form = document.getElementById('addCourseForm');
    if (form) {
        form.reset();
    }
    editingCourse = null;
}

function editCourse(courseId) {
    const course = courses.find(c => c.id === courseId);
    if (!course) return;
    
    editingCourse = course;
    const form = document.getElementById('addCourseForm');
    if (form) {
        form.courseCode.value = course.code;
        form.courseName.value = course.name;
        form.credits.value = course.credits;
        form.program.value = course.program;
        form.semester.value = course.semester;
        form.maxStudents.value = course.maxStudents;
        form.description.value = course.description;
        form.instructor.value = course.instructorId || '';
    }
}

function deleteCourse(courseId) {
    if (confirm('Are you sure you want to delete this course?')) {
        courses = courses.filter(c => c.id !== courseId);
        renderCourses();
        updateStats();
    }
}

function searchCourses() {
    filterCourses();
}

function filterCourses() {
    const searchInput = document.getElementById('searchCourses');
    const statusFilter = document.getElementById('statusFilter');
    const programFilter = document.getElementById('programFilter');
    
    if (!searchInput || !statusFilter || !programFilter) return;
    
    const searchTerm = searchInput.value.toLowerCase();
    const statusValue = statusFilter.value;
    const programValue = programFilter.value;
    
    const rows = document.querySelectorAll('#coursesTableBody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const showSearch = searchTerm === '' || text.includes(searchTerm);
        const showStatus = statusValue === '' || text.includes(statusValue);
        const showProgram = programValue === '' || text.includes(programValue);
        
        row.style.display = showSearch && showStatus && showProgram ? '' : 'none';
    });
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.overlay');
    
    if (sidebar) sidebar.classList.toggle('open');
    if (overlay) overlay.classList.toggle('active');
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.overlay');
    
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('active');
}

function showPage(page) {
    console.log('Showing page:', page);
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '/login';
    }
}

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addCourseForm');
    if (form) {
        form.addEventListener('submit', handleCourseFormSubmit);
    }
    
    renderCourses();
    updateStats();
});

// Test if the table exists
setTimeout(function() {
    console.log('Testing table...');
    const tbody = document.getElementById('coursesTableBody');
    if (tbody) {
        console.log('Table found, rendering courses...');
        renderCourses();
    } else {
        console.log('Table not found!');
    }
}, 1000);