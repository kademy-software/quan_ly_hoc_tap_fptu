let answeredCount = 0;
let totalQuestions = document.querySelectorAll('.question-block').length;

async function updateAnswer(questionId, answerId, assessmentId, attemptId) {
    try {
        const res = await apiCall('api/assessments.php', 'POST', {
            action: 'save_response',
            attempt_id: attemptId,
            question_id: questionId,
            answer_id: answerId
        });
        
        // Cập nhật hiển thị
        const block = document.querySelector(`[data-question-id="${questionId}"]`);
        if (res.is_correct) {
            block.querySelector(`#status-${questionId}`).className = 'badge badge-green';
            block.querySelector(`#status-${questionId}`).textContent = 'Đúng';
        } else {
            block.querySelector(`#status-${questionId}`).className = 'badge badge-orange';
            block.querySelector(`#status-${questionId}`).textContent = 'Sai (?)';
        }
        
        updateProgress();
    } catch (err) {
        alert(err.message);
    }
}

function updateProgress() {
    const answered = Array.from(document.querySelectorAll('.question-block')).filter(el => {
        const qid = el.dataset.questionId;
        return document.querySelector(`input[name="answer_${qid}"]:checked`);
    }).length;
    
    document.getElementById('progress').textContent = answered;
    const percent = (answered / totalQuestions) * 100;
    document.getElementById('progressBar').style.width = percent + '%';
}

async function submitAssessment(attemptId, assessmentId) {
    if (!confirm('Bạn chắc chắn muốn nộp bài? Không thể sửa lại sau khi nộp.')) return;
    
    try {
        const res = await apiCall('api/assessments.php', 'POST', {
            action: 'submit',
            attempt_id: attemptId
        });
        
        // Chuyển sang trang kết quả
        window.location.href = `assessment_result.php?attempt_id=${attemptId}`;
    } catch (err) {
        alert(err.message);
    }
}
