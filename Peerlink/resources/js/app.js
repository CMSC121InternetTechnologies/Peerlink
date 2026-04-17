//  USER PROFILE DATA (TEMP STATE)
let userProfile = {
    bio: "Computer Science student. I love coding and helping others learn! Wow.",
    tutorCourses: ["CMSC 11", "CMSC 21"],
    tuteeCourses: ["MATH 55"]
};

// Tracks current mode (tutor / tutee)
let currentMode = 'tutee';


// INITIALIZATION
document.addEventListener('DOMContentLoaded', () => {
    setMode('tutee');
    updateProfileDisplay();

    // Character counter for bio input
    const bioInput = document.getElementById('bioInput');
    if (bioInput) {
    bioInput.addEventListener('input', () => {
        document.getElementById('charCount').textContent = bioInput.value.length;
    });
  }
});

// MODE TOGGLE (Tutor / Tutee)
function setMode(mode) {
    currentMode = mode;

    // Update body class (controls theme)
    document.body.className = `mode-${mode}`;
    const roleBadge = document.getElementById('roleBadge');

    // Update role badge text and color
    if (mode === 'tutor') {
        roleBadge.textContent = "TUTOR PROFILE";
        roleBadge.style.background = "var(--teal-dark)";
    } else {
        roleBadge.textContent = "TUTEE PROFILE";
        roleBadge.style.background = "var(--coral)";
    }
}

// VIEW / EDIT MODE TOGGLE
function toggleEditMode(isEdit) {
    const viewMode = document.getElementById('profileViewMode');
    const editMode = document.getElementById('profileEditMode');

    viewMode.style.display = isEdit ? 'none' : 'block';
    editMode.style.display = isEdit ? 'block' : 'none';

    // Pre-fill form when entering edit mode
    if (isEdit) {
        document.getElementById('bioInput').value = userProfile.bio;
        renderTags('tutor');
        renderTags('tutee');
    }
}


// ADD COURSE (Tutor / Tutee)
function addCourse(type) {
    const select = document.getElementById(`${type}Select`);
    const selectedValue = select.value;

    // Choose correct list based on type
    const courseList = type === 'tutor'
        ? userProfile.tutorCourses
        : userProfile.tuteeCourses;

    // Prevent duplicates
    if (selectedValue && !courseList.includes(selectedValue)) {
        courseList.push(selectedValue);
        renderTags(type);
    }

    // Reset dropdown
    select.selectedIndex = 0;
}


// RENDER COURSE TAGS (EDIT MODE)
function renderTags(type) {
    const container = document.getElementById(`${type}Tags`);

    const courseList = type === 'tutor'
        ? userProfile.tutorCourses
        : userProfile.tuteeCourses;

    // Generate tag elements
    container.innerHTML = courseList.map(course => `
    <span class="filter-tag">
        ${course}
        <button type="button" onclick="removeTag('${course}', '${type}')">✕</button>
    </span>
    `).join('');
}


// REMOVE COURSE TAG
function removeTag(course, type) {
    if (type === 'tutor') {
    userProfile.tutorCourses =
        userProfile.tutorCourses.filter(c => c !== course);
    } else {
    userProfile.tuteeCourses =
        userProfile.tuteeCourses.filter(c => c !== course);
    }

    renderTags(type);
}


// SAVE PROFILE
function saveProfile(event) {
    event.preventDefault();
    
    // Update data from form
    userProfile.bio = document.getElementById('bioInput').value;

    // Re-render display
    updateProfileDisplay();

    // Exit edit mode
    toggleEditMode(false);

    // Feedback
    showToast("Profile updated!");
}


// UPDATE PROFILE DISPLAY (VIEW MODE)
function updateProfileDisplay() {
    // Bio
    document.getElementById('bioDisplay').textContent =
    userProfile.bio || "No bio added yet.";

    // Tutor courses
    document.getElementById('tutorCoursesDisplay').innerHTML =
    userProfile.tutorCourses
        .map(course => `<span class="course-badge">${course}</span>`)
        .join('');

    // Tutee courses
    document.getElementById('tuteeCoursesDisplay').innerHTML =
    userProfile.tuteeCourses
        .map(course => `<span class="course-badge">${course}</span>`)
        .join('');
    }

// DELETE MODAL CONTROLS
function openDeleteModal() {
    document.getElementById('deleteModal').classList.add('open');
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('open');
}


// TOAST NOTIFICATION
function showToast(message) {
    const toast = document.getElementById('toast');

    toast.textContent = message;
    toast.classList.add('show');

    // Auto-hide after 3s
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}