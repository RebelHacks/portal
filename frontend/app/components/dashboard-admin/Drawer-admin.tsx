import style from "../../dashboard/dashboard.module.css";

const TABS = ["Arrivals", "Teams"];

export function Drawer({
  isDrawerOpen,
  setIsDrawerOpen,
  activeTab,
  setActiveTab,
}: {
  isDrawerOpen: boolean;
  setIsDrawerOpen: (isOpen: boolean) => void;
  activeTab: string;
  setActiveTab: (tab: string) => void;
}) {
  return (
    <>
      {isDrawerOpen && (
        <>
          <div className={style.drawerBackdrop} onClick={() => setIsDrawerOpen(false)} />

          <aside className={style.drawerPanel}>
            <div className={style.drawerHeader}>
              <div className="text-xl font-bold tracking-widest text-(--primary)">MENU</div>
              <button onClick={() => setIsDrawerOpen(false)} className={style.closeButton}>
                ✕
              </button>
            </div>

            <nav className="space-y-2">
              {TABS.map((option) => (
                <div
                  key={option}
                  onClick={() => {
                    setActiveTab(option);
                    setIsDrawerOpen(false);
                  }}
                  className={`${style.option} ${activeTab === option ? style.active : ""} px-4 py-3 rounded text-lg`}>
                  {option}
                </div>
              ))}
            </nav>
          </aside>
        </>
      )}
    </>
  );
}
