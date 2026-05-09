{{-- US_16: student leaves a star rating + feedback after a Completed session.
     The rating bumps the tutor's rating_avg in TutorProfile (recomputed
     server-side; see ReviewController::store). --}}
<div class="modal-overlay" id="reviewModalOverlay" onclick="closeReviewModal()">
  <div class="modal" onclick="event.stopPropagation()">
    <button class="modal-close" onclick="closeReviewModal()">✕</button>
    <h2 class="mb-xs">Leave a Review</h2>
    <p id="reviewTutorName" class="modal-subtle mb-md"></p>
    <input type="hidden" id="reviewSessionId"/>
    <input type="hidden" id="reviewTutorId"/>
    <div class="modal-form">
      <label for="starRating">Rating</label>
      <div class="star-rating" id="starRating">
        <span class="star-btn" data-val="1">★</span>
        <span class="star-btn" data-val="2">★</span>
        <span class="star-btn" data-val="3">★</span>
        <span class="star-btn" data-val="4">★</span>
        <span class="star-btn" data-val="5">★</span>
      </div>
      <input type="hidden" id="reviewRating" value="0"/>
      <label for="reviewFeedback" class="mt-sm">Feedback (optional)</label>
      <textarea id="reviewFeedback" placeholder="Share your experience…"></textarea>
      <button class="btn-primary full-width mt-md" onclick="submitReview()">Submit Review</button>
    </div>
  </div>
</div>
