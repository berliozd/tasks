export const STATUS_LABELS = {
    pending: 'pending', planned: 'planned', sent: 'sent', done: 'done', replied: 'replied', bounced: 'bounced',
    no_response: 'no response', lost: 'lost',
};

export const STATUS_COLORS = {
    replied: 'bg-blue-50 text-blue-700',
    bounced: 'bg-red-50 text-red-700',
    no_response: 'bg-red-50 text-red-700',
    lost: 'bg-red-50 text-red-700',
    sent: 'bg-brand-accent/10 text-brand-accent-dark',
    done: 'bg-brand-accent/10 text-brand-accent-dark',
    planned: 'bg-brand-accent/10 text-brand-accent-dark',
    pending: 'bg-gray-100 text-gray-600',
};

// 'done' covers non-email logged actions (linkedin/call/meeting/other) —
// they never reach 'sent', which only applies to sent emails.
export const STATUS_ORDER = ['sent', 'done', 'replied', 'lost', 'bounced', 'no_response', 'planned', 'pending'];

// Turns a {status: count} map (as returned by the backend's action status
// breakdowns) into an ordered list of non-zero flags ready to render as badges.
export const statusFlags = (counts) => STATUS_ORDER
    .map(status => ({status, count: counts?.[status] ?? 0}))
    .filter(s => s.count > 0)
    .map(s => ({...s, label: `${s.count} ${STATUS_LABELS[s.status]}`, colorClass: STATUS_COLORS[s.status]}));
