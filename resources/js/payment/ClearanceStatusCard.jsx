import DocCheck from '../components/DocCheck';

export default function ClearanceStatusCard({ cashierCleared, registrarCleared, chairCleared }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
            <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white mb-2">Clearance status</h3>
            <div className="ui-doc-list border-brandNavy/8 dark:border-slate-800">
                <div className="ui-doc-row border-brandNavy/8 dark:border-slate-800">
                    <DocCheck tone={cashierCleared ? 'done' : 'pending'} />
                    <p className="text-sm text-brandNavy/70 dark:text-slate-400">Accounting Office — balance assessment</p>
                </div>
                <div className="ui-doc-row border-brandNavy/8 dark:border-slate-800">
                    <DocCheck tone={registrarCleared ? 'done' : 'hold'} />
                    <p className="text-sm text-brandNavy/70 dark:text-slate-400">Registrar — administrative document verification</p>
                </div>
                <div className="ui-doc-row border-brandNavy/8 dark:border-slate-800">
                    <DocCheck tone={chairCleared ? 'done' : 'pending'} />
                    <p className="text-sm text-brandNavy/70 dark:text-slate-400">Department Head — curriculum evaluation</p>
                </div>
            </div>
        </div>
    );
}
