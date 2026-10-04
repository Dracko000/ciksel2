<!DOCTYPE html>
<html>
<head>
    <title>E-Raport - SDN Cikampek Selatan 2</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .school-name { font-size: 24px; font-weight: bold; }
        .raport-title { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 20px; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px; }
        .grade-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .grade-table th, .grade-table td { border: 1px solid #000; padding: 10px; text-align: left; }
        .grade-table th { background-color: #f2f2f2; }
        .footer { margin-top: 50px; }
        .signature { float: right; width: 200px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">SDN CIKAMPEK SELATAN 2</div>
        <div>Alamat: Jl. Cikampek No. 123, Karawang, Jawa Barat</div>
        <div>Telepon: (021) 12345678 | Email: sdn.ciksel2@gmail.com</div>
    </div>

    <div class="raport-title">LAPORAN HASIL BELAJAR SISWA (E-RAPORT)</div>

    <table class="info-table">
        <tr>
            <td width="20%">Nama Siswa</td>
            <td width="2%">:</td>
            <td><strong>{{ $siswa->nama }}</strong></td>
            <td width="20%">Semester</td>
            <td width="2%">:</td>
            <td>Ganjil</td>
        </tr>
        <tr>
            <td>NIS</td>
            <td>:</td>
            <td>{{ $siswa->nis }}</td>
            <td>Tahun Ajaran</td>
            <td>:</td>
            <td>2023/2024</td>
        </tr>
        <tr>
            <td>Kelas</td>
            <td>:</td>
            <td>{{ $siswa->kelas->nama_kelas ?? '-' }}</td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <table class="grade-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Mata Pelajaran</th>
                <th width="15%">Nilai Angka</th>
                <th width="15%">Predikat</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($nilais as $index => $nilai)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $nilai->mata_pelajaran }}</td>
                <td style="text-align: center;">{{ $nilai->nilai_angka }}</td>
                <td style="text-align: center;">
                    @if($nilai->nilai_angka >= 85) A @elseif($nilai->nilai_angka >= 75) B @else C @endif
                </td>
                <td>{{ $nilai->catatan_guru ?? 'Cukup baik.' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Kehadiran</h3>
    <table class="grade-table" style="width: 50%;">
        <tr>
            <td>Total Kehadiran Bulan Ini</td>
            <td><strong>{{ $kehadiran }} Hari</strong></td>
        </tr>
    </table>

    <div class="footer">
        <div class="signature">
            <p>Karawang, {{ $tanggal }}</p>
            <p>Wali Kelas,</p>
            <br><br><br>
            <p>__________________________</p>
            <p>NIP. ..............................</p>
        </div>
    </div>
</body>
</html>
