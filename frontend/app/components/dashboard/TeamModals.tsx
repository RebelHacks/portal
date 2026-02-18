import style from "../../dashboard/dashboard.module.css";

interface TeamModalsProps {
  state: {
    error: string | null;
    showDisband: boolean;
    teamName: string;
    isSoloLeader: boolean;
  };
  onAction: (action: "closeError" | "cancelDisband" | "confirmDisband") => void;
}

export function TeamModals({ state, onAction }: TeamModalsProps) {
  const { error, showDisband, teamName, isSoloLeader } = state;

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
            <h2 className={style.secondaryTitle}>
              {isSoloLeader ? "Leave Team?" : "Disband Team?"}
            </h2>
            <p className="mb-6 text-(--sub-text)">
              {isSoloLeader ? "You are the only member of" : "Are you sure you want to disband"}{" "}
              <span className="text-(--primary) font-bold">
                &quot;{teamName}&quot;
              </span>
              ? {isSoloLeader ? "Leaving will remove this empty team." : "This action cannot be undone."}
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
                {isSoloLeader ? "Leave Team" : "Disband Now"}
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
