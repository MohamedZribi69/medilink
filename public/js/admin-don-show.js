document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('btn-check-text');
    var resultDiv = document.getElementById('text-check-result');
    var loadingEl = document.getElementById('text-check-loading');
    var contentEl = document.getElementById('text-check-content');
    var formApply = document.getElementById('form-apply-correction');
    var inputDesc = document.getElementById('input-article-description');
    var inputDetails = document.getElementById('input-details-supplementaires');

    if (!btn) return;

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    btn.addEventListener('click', function() {
        var url = btn.getAttribute('data-url');
        resultDiv.style.display = 'block';
        contentEl.innerHTML = '';
        formApply.style.display = 'none';
        loadingEl.style.display = 'block';

        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                loadingEl.style.display = 'none';

                var html = '';
                var correctedDesc = (data.correctedArticleDescription != null) ? String(data.correctedArticleDescription) : '';
                var correctedDet = (data.correctedDetailsSupplementaires != null) ? String(data.correctedDetailsSupplementaires) : '';
                var origDesc = (data.articleDescription != null) ? String(data.articleDescription) : '';
                var origDet = (data.detailsSupplementaires != null) ? String(data.detailsSupplementaires) : '';
                var hasCorrection = (correctedDesc !== origDesc) || (correctedDet !== origDet);

                if (!data.hasIssues && !hasCorrection) {
                    html = '<p class="text-success mb-0"><i class="fas fa-check-circle me-1"></i> Aucune correction suggérée.</p>';
                } else {
                    if (data.hasIssues) {
                        var sDesc = (data.suggestions.articleDescription || []).length;
                        var sDet = (data.suggestions.detailsSupplementaires || []).length;
                        html += '<p class="small mb-2"><strong>' + (sDesc + sDet) + ' suggestion(s) détectée(s).</strong></p>';
                        if (sDesc > 0) {
                            html += '<p class="small mb-1"><strong>Description :</strong></p>';
                            html += '<ul class="small mb-2">';
                            data.suggestions.articleDescription.forEach(function(s) {
                                html += '<li>' + (s.message || '') + ' : « ' + (s.original || '') + ' » → « ' + (s.replacement || '') + ' »</li>';
                            });
                            html += '</ul>';
                            html += '<p class="small text-muted mb-1">Texte corrigé :</p><div class="p-2 bg-light rounded small mb-2">' + escapeHtml(correctedDesc) + '</div>';
                        }
                        if (sDet > 0) {
                            html += '<p class="small mb-1"><strong>Détails supplémentaires :</strong></p>';
                            html += '<ul class="small mb-2">';
                            data.suggestions.detailsSupplementaires.forEach(function(s) {
                                html += '<li>' + (s.message || '') + ' : « ' + (s.original || '') + ' » → « ' + (s.replacement || '') + ' »</li>';
                            });
                            html += '</ul>';
                            html += '<p class="small text-muted mb-1">Texte corrigé :</p><div class="p-2 bg-light rounded small mb-2">' + escapeHtml(correctedDet) + '</div>';
                        }
                    }
                    if (hasCorrection && !data.hasIssues) {
                        html += '<p class="small mb-2">Texte corrigé disponible ci-dessous.</p>';
                        if (correctedDesc !== origDesc) {
                            html += '<p class="small text-muted mb-1">Description corrigée :</p><div class="p-2 bg-light rounded small mb-2">' + escapeHtml(correctedDesc) + '</div>';
                        }
                        if (correctedDet !== origDet) {
                            html += '<p class="small text-muted mb-1">Détails corrigés :</p><div class="p-2 bg-light rounded small mb-2">' + escapeHtml(correctedDet) + '</div>';
                        }
                    }

                    inputDesc.value = correctedDesc;
                    inputDetails.value = correctedDet;
                    formApply.style.display = 'block';
                }
                contentEl.innerHTML = html;
            })
            .catch(function() {
                loadingEl.style.display = 'none';
                contentEl.innerHTML = '<p class="text-danger mb-0"><i class="fas fa-exclamation-triangle me-1"></i> Impossible de contacter le service de vérification. Réessayez plus tard.</p>';
            });
    });

    if (formApply) {
        formApply.addEventListener('submit', function(e) {
            e.preventDefault();
            var form = this;
            var submitBtn = form.querySelector('button[type="submit"]');
            var originalHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Application…';
            }
            var formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                body: formData,
                redirect: 'follow',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(response) {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    window.location.reload();
                }
            })
            .catch(function() {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                }
                alert('Erreur lors de l\'application des corrections. Réessayez.');
            });
        });
    }
});
