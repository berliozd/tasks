// Mirrors App\Models\Team::PAID_FEATURES / hasFeatureEnabled() on the backend
// — kept in sync manually since this is just for hiding nav/dashboard UI;
// the real enforcement is server-side (EnsureFeatureEnabled middleware).
export const PAID_FEATURES = ['prospection', 'documents', 'needs'];

export const isTeamFeatureEnabled = (team, feature) => {
    const disabled = team?.disabled_features ?? [];
    if (disabled.includes(feature)) return false;
    if (PAID_FEATURES.includes(feature) && !team?.is_pro) return false;
    return true;
};
