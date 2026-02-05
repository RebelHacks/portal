import style from "../../dashboard/dashboard.module.css";

export function Header({ setIsDrawerOpen }: { setIsDrawerOpen: (isOpen: boolean) => void }) {
  return (
    <header className={style.header}>
        <div className="flex items-center gap-4">
          <button 
            onClick={() => setIsDrawerOpen(true)}
            className={style.hamburgerButton}
          >
            <span className="text-xl leading-none">☰</span>
          </button>
          
          <h1 className="text-lg md:text-xl font-bold tracking-widest text-[var(--primary)] ml-2">
            REBEL HACKS
          </h1>
        </div>

        <div className="flex items-center gap-3">
           <div className={style.memberAvatar}>
             👤
           </div>
        </div>
      </header>
  );
}