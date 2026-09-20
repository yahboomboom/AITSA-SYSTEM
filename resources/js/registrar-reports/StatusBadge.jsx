export default function StatusBadge({ status }) {
    if (status === 'Approved') {
        return <span className="ui-badge-outline border-brandGreen text-brandGreen">Approved</span>;
    }
    if (status === 'Hold') {
        return <span className="ui-badge-outline border-red-500 text-red-600">Hold</span>;
    }
    return <span className="ui-badge-outline border-brandGold text-brandGold">Pending</span>;
}
