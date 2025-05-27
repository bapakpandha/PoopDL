import Header from "../../__components/Header";
import Footer from "../../__components/Footer";
import HistoryMain from "../_components/HistoryMain";

export default function History() {
  return (
    <div className="relative flex min-h-screen flex-col">
      <Header />
      <main className="flex flex-1 flex-col items-center mt-2">
        <div className="flex flex-col items-center justify-center w-full max-w-5xl p-4 bg-white dark:bg-gray-800">
          <HistoryMain />
        </div>
      </main>
      <Footer />
    </div>
  );
}
