/* eslint-disable @typescript-eslint/no-explicit-any */
export async function getHistory(payload: any) {
    const res = await fetch("http://127.0.0.13/api/v2/history/", {
    // const res = await fetch("/poop_dl/api/v2/history/", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ ...payload }),
    });

    if (!res.ok) throw new Error("Gagal melakukan request");
    return res.json();
}

export interface HistoryResult {
    status: "success" | "error";
    message: string;
    data?: {
        result?: {
            video_data?: ResultItem[];
            folder_data?: ResultFolder[];
        }
        pagination_num?: number;
    };
    logs?: any[];
}

export interface ResultItem {
    id: number;
    video_url: string;
    title: string;
    thumbnail_url: string;
    summary_url: string;
    video_src: string;
    fetched_at: string;
    size: string;
    length: string;
}

export interface ResultFolder {
    id: number;
    folder_url: string;
    title: string;
    fetched_at: string;
    total_video: number;
    data: ResultItem[];
}

export const dummyResults = [
    {
        "status": "success",
        "message": "Data berhasil diambil",
        "data": {
            "result": {
                "video_data": [
                    {
                        "id": 1,
                        "video_url": "https://example.com/videos/1",
                        "title": "Tutorial TypeScript",
                        "thumbnail_url": "https://example.com/thumbnails/1.jpg",
                        "summary_url": "https://example.com/summary/1",
                        "video_src": "https://cdn.example.com/vid/1.mp4",
                        "fetched_at": "2025-05-27T14:30:00Z",
                        "size": "120MB",
                        "length": "10:45"
                    },
                    {
                        "id": 2,
                        "video_url": "https://example.com/videos/2",
                        "title": "React Basics",
                        "thumbnail_url": "https://example.com/thumbnails/2.jpg",
                        "summary_url": "https://example.com/summary/2",
                        "video_src": "https://cdn.example.com/vid/2.mp4",
                        "fetched_at": "2025-05-26T10:00:00Z",
                        "size": "95MB",
                        "length": "08:30"
                    }
                ],
                "folder_data": [
                    {
                        "id": 10,
                        "folder_url": "https://example.com/folders/10",
                        "title": "Frontend Series",
                        "fetched_at": "2025-05-25T09:00:00Z",
                        "total_video": 2,
                        "data": [
                            {
                                "id": 3,
                                "video_url": "https://example.com/videos/3",
                                "title": "CSS Grid Layout",
                                "thumbnail_url": "https://example.com/thumbnails/3.jpg",
                                "summary_url": "https://example.com/summary/3",
                                "video_src": "https://cdn.example.com/vid/3.mp4",
                                "fetched_at": "2025-05-25T09:15:00Z",
                                "size": "80MB",
                                "length": "07:20"
                            },
                            {
                                "id": 4,
                                "video_url": "https://example.com/videos/4",
                                "title": "Flexbox Mastery",
                                "thumbnail_url": "https://example.com/thumbnails/4.jpg",
                                "summary_url": "https://example.com/summary/4",
                                "video_src": "https://cdn.example.com/vid/4.mp4",
                                "fetched_at": "2025-05-25T09:30:00Z",
                                "size": "85MB",
                                "length": "06:50"
                            }
                        ]
                    },
                    {
                        "id": 11,
                        "folder_url": "https://example.com/folders/10",
                        "title": "Frontend Series",
                        "fetched_at": "2025-05-25T09:00:00Z",
                        "total_video": 2,
                        "data": [
                            {
                                "id": 3,
                                "video_url": "https://example.com/videos/3",
                                "title": "CSS Grid Layout",
                                "thumbnail_url": "https://example.com/thumbnails/3.jpg",
                                "summary_url": "https://example.com/summary/3",
                                "video_src": "https://cdn.example.com/vid/3.mp4",
                                "fetched_at": "2025-05-25T09:15:00Z",
                                "size": "80MB",
                                "length": "07:20"
                            },
                            {
                                "id": 4,
                                "video_url": "https://example.com/videos/4",
                                "title": "Flexbox Mastery",
                                "thumbnail_url": "https://example.com/thumbnails/4.jpg",
                                "summary_url": "https://example.com/summary/4",
                                "video_src": "https://cdn.example.com/vid/4.mp4",
                                "fetched_at": "2025-05-25T09:30:00Z",
                                "size": "85MB",
                                "length": "06:50"
                            }
                        ]
                    }
                ]
            },
            "pagination_num": 1
        },
        "logs": [
            { "action": "fetch", "timestamp": "2025-05-27T14:30:00Z", "status": "completed" }
        ]
    }

]