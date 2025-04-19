Update Metode Scrapping Video V2 (25.04.18) API long polling method (Client mengirim request, server menunggu proses selesai sebagian, lalu merespons. Setelah dapat respons, client langsung kirim request baru lagi.)
1. FE mengirim json mengandung url
2. BE menerima json, memvalidasi url dengan fungsi validateUrl(url) dengan regex preg_match('/https?:\/\/(.+?)\/(d|e)\/([a-zA-Z0-9]+)/', $url, $matches). jika valid, BE merespon pesan "Memproses Tautan..." ke FE. jika tidak, merespon pesan "Tautan tidak valid" ke FE.
3. FE lanjut mengirim request ke BE untuk 'menyuruh' BE melanjutkan proses
4. BE menerima permintaan FE, lalu memproses url dengan curl ke url dengan option follow redirect. Hasil curl berupa html. Jika http status response berhasil (bukan 4xx, atau 5xx) Cari dengan regex ke hasil curl untuk mendapatkan:
 - (tidak wajib ditemukan) value dari <title> di dalam tag <head>. Simpan 'title'nya.
 - (tidak wajib ditemukan) Kemudian lanjutkan mencocokkan <div class="length"> 03:32 </div> atau <div class="length"> 43:30 </div>. dapatkan value dari div tersebut (03.32 atau 43.30)
 - (tidak wajib ditemukan) Kemudian lanjutkan mencocokkan <div class="size"> 107.47 MB </div>
 - (tidak wajib ditemukan) Kemudian lanjutkan mencocokkan <div class="uploadate"> 17 April 2025 </div>
 - (wajib ditemukan) Kemudian lanjutkan mencocokkan pola: 
    - 'poopiframe','https://berlagu.com/jembud/','length','6174746f7573633975657875', atau
    - 'poopiframe','https://berlagu.com/jembud/','length','726a666a717463636a706735', atau
    - 'poopiframe','https://berlagu.com/jembud/','length','373472396b3236627a733338',
    Value yang didapatkan adalah alfanumerik setelah 'length', yaitu 6174746f7573633975657875 atau 726a666a717463636a706735 atau 373472396b3236627a733338 (kita sebut metrolagu_post_id)
    Jika pola wajib diatas tidak ditemukan, maka BE merespon dengan pesan gagal
    Jika pola wajib ditemukan, maka BE mengembalikan response berisi title, length, size, dan alfanumerik diatas yang kita sebut metrolagu_post_id beserta pesan sukses
5. FE menerima respon dari BE, lalu mengirimkan kembali request untuk 'menyuruh' BE melanjutkan proses.
6. BE menerima permintaan dari FE, melanjutkan proses selanjutnya. Proses selanjutnya adalah melakukan POST request ke https://www.metrolagu.cam/watch dengan body form-data key:poop value:{metrolagu_post_id} 
  Jika POST request tersebut mendapat response 200 OK, maka mencari pola tersebut di html:
 - (wajib ditemukan) mencocokkan pola seperti ini:  
    -     <script type="text/javascript">
            var videoId = 'uxeu9csuotta';
            var ipx = 'MTU4LjE0MC4xODIuNzU=';
            var baseURL = "https://poophd.video-src.com/";
            var playerPath = 'pplayer?id=uxeu9csuotta';
            var timer = '48e3';
            var fullURL = baseURL + playerPath;

    atau

        <script type="text/javascript">
            var videoId = '5gpjcctqjfjr';
            var ipx = 'MjAwMTo0NDhhOjExNGY6MTlmZDo5ZGYxOg==';
            var baseURL = "https://poophd.video-src.com/";
            var playerPath = 'vplayer?id=5gpjcctqjfjr';
            var timer = '35e3';
            var fullURL = baseURL + playerPath;

    dapatkan videoId, baseURL, playerPath, dan fullURL.
    Jika pola diatas tidak ditemukan, maka proses tidak dilanjutkan dan BE merespon dengan pesan gagal.
    Jika ditemukan, maka informasikan hasilnya ke FE, yaitu videoID, baseURL, playerPath, dan fullURL.
7. FE menerima pesan dari BE bahwa tahap scrapping sudah sampai di tahap ini, lalu FE menginformasikannya ke user, kemudian FE kembali mengirim request untuk melanjutkan process.
8. BE menerima perintah untuk melanjutkan process. Process selanjutnya adalah scrapping ke fullURL. BE akan melakukan curl ke fullURL dengan tambahan header Referer: https://www.metrolagu.cam/, lalu mencari pola berikut:
    - function initializePlayer() {

        player("a", "https://video-src.com/VsMLC.jpg", "l", "/xstream?key=VsMLC-Lagi Bersihin Kebun Mala ngewe - DoodStream - DoodStream.mp4&filename=jalan² di kebun berakhir n9en");

    atau

    - function initializePlayer() {

            player("a", "https://video-src.com/VkVrCdkuk.jpg", "l", "https://video-src.com/VkVrCdkuk-fb4zzd1lokp3.mp4");

    }

    Jika berhasil menemukan pola seperti diatas, dapatkan value dari parameter ketiga dari, fungsi player yaitu /xstream?key=VsMLC-Lagi Ber... atau https://video-src.com/VkVrC...
    Jika berbentuk (/xstream?key=VsMLC-Lagi Ber...) maka tambahkan baseURL sehingga menjadi https://poophd.video-src.com/xstream?key=VsMLC-Lagi Ber...
    Lalu informasikan ke FE url tersebut. dan katakan ke FE bahwa scrapping tahap ke 8 berhasil.