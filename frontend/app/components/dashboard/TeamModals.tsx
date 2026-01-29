import style from "../../dashboard/dashboard.module.css";

interface TeamModalsProps {
  state: {
    error: string | null;
    showDisband: boolean;
    teamName: string;
  };
  onAction: (action: "closeError" | "cancelDisband" | "confirmDisband") => void;
}

export function TeamModals({ state, onAction }: TeamModalsProps) {
  const { error, showDisband, teamName } = state;

  return (
    <>
      {error && (
        <div className={style.modalBackdrop} style={{ zIndex: 70 }}>
          <div className={style.card}>
            <h2 className={style.secondaryTitle}>Attention!</h2>
            <p className="mb-6">{error}</p>
            <button 
              onClick={() => onAction("closeError")} 
              className={style.primaryButton}
            >
              Got it
            </button>
          </div>
        </div>
      )}

      {showDisband && (
        <div className={style.modalBackdrop} style={{ zIndex: 70 }}>
          <div className={style.card}>
            <h2 className={style.secondaryTitle}>Disband Team?</h2>
            <p className="mb-6">
              Are you sure you want to disband <span className="text-[var(--primary)] font-bold">"{teamName}"</span>? This action cannot be undone.
            </p>
            <div className="flex gap-5">
              <button 
                onClick={() => onAction("cancelDisband")} 
                className={style.secondaryButton}
              >
                Cancel
              </button>
              <button 
                onClick={() => onAction("confirmDisband")} 
                className={style.warnButton}
              >
                Disband Now
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}