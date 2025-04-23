import React from 'react'
import { scrapeVideoStep } from '@/app/(home)/_utils/api';
import { ScrapeResponse } from '@/app/(home)/_utils/api';
import { FileVideo } from 'lucide-react'
import saveAs from 'file-saver';
import 'video.js/dist/video-js.css';

/* eslint-disable @typescript-eslint/no-explicit-any */
/* eslint-disable @typescript-eslint/no-unused-vars */
const ResultCard = ({ indexNum, urls }: { indexNum: number, urls: string }) => {
    const [errorMessage, setErrorMessage] = React.useState("");
    const [isScrapped, setIsScrapped] = React.useState(false);
    const [isLoading, setIsLoading] = React.useState(false);
    const [isError, setIsError] = React.useState(false);
    const [data, setData] = React.useState<any>({});
    const [log, setLog] = React.useState<string[]>([]);
    const [isVideoView, setIsVideoView] = React.useState(false);
    const [isThumbnailHidden, setIsThumbnailHidden] = React.useState(true)

    const handleFetchVideo = async () => {
        setIsLoading(true);
        setIsError(false);
        setIsScrapped(false);
        setLog([]);
        setData({});

        const url = urls;

        let currentStep = 1;
        let payload: any = { url };
        let accumulatedData: any = {};

        try {
            while (currentStep < 10) {
                const res: ScrapeResponse = await scrapeVideoStep(currentStep, payload);
                setLog((prev) => [...prev, `${res.message}`]);
                payload = { ...payload, ...res.data };
                accumulatedData = { ...accumulatedData, ...res.data };
                setData(accumulatedData);
                currentStep = res.step + 1;
                if (res.status === "error") break;
                if (!(accumulatedData.video_src === undefined || accumulatedData.video_src === null)) break;
            }

            if (accumulatedData.video_src) {
                setTimeout(() => {
                    setData(accumulatedData);
                    setLog((prev) => [...prev, "Scraping completed."]);
                    setIsScrapped(true);
                }, 1000);
            }

            if (accumulatedData.video_src === undefined || accumulatedData.video_src === null) {
                setLog((prev) => [...prev, "Scraping failed."]);
                setIsError(true);
                setErrorMessage("Failed to scrape the video. Please try again.");
            }

        } catch (e) {
            setIsError(true);
            setLog((prev) => [...prev, `Error: ${e instanceof Error ? e.message : 'Unknown error'}`]);
            setErrorMessage("An error occurred while scraping the video. Please try again.");
        } finally {
            setIsLoading(false);
        }
    }

    const handleDownload = () => {
        if (data.video_src) {
            saveAs(data.video_src, `${data.title}.mp4`);
        } else {
            setLog((prev) => [...prev, "No video source available."]);
        }
    };

    const viewVideoHandle = () => {
        setIsThumbnailHidden(false);
        setIsVideoView((s) => !s);
    };

    const showThumbnailHandle = () => {
        setIsThumbnailHidden((s) => !s);
    };

    return (
        <div className='mt-2'>
            {isScrapped && !isError && !isLoading && data.video_src ? (
                <div className="wrapper overflow-hidden flex max-h-96 flex-row items-center justify-between px-4">
                    <div className={`${isThumbnailHidden ? "hidden" : ""}`}>
                        {!isVideoView ? (
                            <img
                                className="max-w-56 sm:max-w-64 md:max-w-96 w-auto max-h-56 sm:max-h-64 md:max-h-96 h-auto rounded-t-lg object-cover md:rounded-none md:rounded-s-lg"
                                src={data.thumbnail || "https://demofree.sirv.com/nope-not-here.jpg"}
                                alt="Thumbnail"
                            />
                        ) : (
                            <div className="video-container mx-auto flex items-center justify-center">
                                <video
                                    id="video-player"
                                    className="max-w-56 sm:max-w-64 md:max-w-96 w-auto max-h-56 sm:max-h-64 md:max-h-96 h-auto video-js vjs-default-skin"
                                    controls
                                    preload="auto"
                                    width="320"
                                    height="240"
                                    data-setup="{}"
                                >
                                    <source src={data.video_src} type="video/mp4" />
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                        )}
                    </div>
                    <div className="flex flex-1 flex-col">
                        <h2 className="mt-4 ml-6 max-w-2xl line-clamp-2 text-left font-semibold text-gray-900 sm:text-2xl">{data.title}</h2>
                        <div className=" ml-4 mt-3 flex flex-col">
                            <div className="flex items-center text-center">
                                <svg className="mx-1.5 flex h-3 w-3 items-center justify-center" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g id="SVGRepo_bgCarrier" strokeWidth="0"></g>
                                    <g id="SVGRepo_tracerCarrier" strokeLinecap="round" strokeLinejoin="round"></g>
                                    <g id="SVGRepo_iconCarrier"><path d="M12 7V12L14.5 10.5M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#000000" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"></path></g>
                                </svg>
                                <p className="flex text-sm text-gray-600">Duration: {data.length}</p>
                            </div>
                            <div className="flex items-center text-center">
                                <svg className="mx-1.5 flex h-3 w-3 items-center justify-center" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g id="SVGRepo_bgCarrier" strokeWidth="0"></g>
                                    <g id="SVGRepo_tracerCarrier" strokeLinecap="round" strokeLinejoin="round"></g>
                                    <g id="SVGRepo_iconCarrier"><path d="M12 7V12L14.5 10.5M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#000000" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"></path></g>
                                </svg>
                                <p className="flex text-sm text-gray-600">Size: {data.size}</p>
                            </div>
                            <div className="flex items-center text-center">
                                <svg className="mx-1.5 flex h-3 w-3 items-center justify-center" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g id="SVGRepo_bgCarrier" strokeWidth="0"></g>
                                    <g id="SVGRepo_tracerCarrier" strokeLinecap="round" strokeLinejoin="round"></g>
                                    <g id="SVGRepo_iconCarrier"><path d="M12 7V12L14.5 10.5M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#000000" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"></path></g>
                                </svg>
                                <p className="flex text-sm text-gray-600">Upload Date: {data.uploadate}</p>
                            </div>
                            <div className="flex items-center text-center">
                                <svg className="mx-1.5 flex h-3 w-3 items-center justify-center" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g id="SVGRepo_bgCarrier" strokeWidth="0"></g>
                                    <g id="SVGRepo_tracerCarrier" strokeLinecap="round" strokeLinejoin="round"></g>
                                    <g id="SVGRepo_iconCarrier"><path d="M12 7V12L14.5 10.5M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#000000" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"></path></g>
                                </svg>
                                <p className="flex text-sm text-gray-600">Url: {data.url}</p>
                            </div>
                        </div>
                        <div className="flex flex-col gap-3 justify-items-start ml-6 mt-4 sm:flex-row">
                            <button onClick={viewVideoHandle} className="rounded bg-purple-700 px-5 py-1.5 sm:text-sm text-xs font-semibold text-white hover:bg-purple-800 focus:ring-4 focus:ring-purple-300 focus:outline-none">
                                {isVideoView ? "view Thumbnail" : "View Video"}
                            </button>
                            <button onClick={handleDownload} className="rounded bg-green-600 px-4 py-1.5 sm:text-sm text-xs font-semibold text-white shadow hover:bg-green-700">Download</button>
                            <button onClick={showThumbnailHandle} className="rounded border border-red-600 px-3 py-1.5 sm:text-sm text-xs font-semibold text-red-600 hover:bg-red-100">{isThumbnailHidden ? "Show Thumbnail" : "Hide Thumbnail"}</button>
                        </div>
                    </div>
                </div>
            ) : (
                <div className='wrapper flex flex-col'>
                    <div className="flex flex-row justify-between items-center">
                        <div className='flex flex-row items-center gap-3'>
                        <FileVideo />
                        <a
                            href={urls}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="font-medium text-blue-600 hover:text-blue-500 hover:underline"
                        >
                            {indexNum + 1}. {" "}  {urls}
                        </a>
                        </div>

                        <button
                            onClick={!isLoading ? handleFetchVideo : undefined}
                            type="button"
                            className="px-3 py-2 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                            disabled={isLoading}
                        >
                            {isLoading ? (
                                <div className="text-center">
                                    <div role="status">
                                        <svg aria-hidden="true" className="inline w-4 h-4 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor" />
                                            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill" />
                                        </svg>
                                        <span className="sr-only">Loading...</span>
                                    </div>
                                </div>
                            ) : (
                                <p>Fetch Video</p>
                            )}
                        </button>
                    </div>
                    {log && log.length > 0 && (
                        <div className="bg-gray-100 dark:bg-gray-800 flex flex-col justify-start mx-auto py-3 rounded-md shadow-md text-center w-full">
                            {log.map((item, index) => (
                                <p key={index} className="text-sm text-gray-700 dark:text-gray-300">{item}</p>
                            ))}
                        </div>
                    )}
                    {isError && errorMessage && (
                        <div className="mt-2 p-2 bg-red-100 text-red-700 rounded-md shadow-md">
                            <p className="text-sm">{errorMessage}</p>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
};

export default ResultCard