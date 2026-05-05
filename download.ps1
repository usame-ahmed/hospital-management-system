$images = @{
    "doctor-Male.jpg" = "https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=1920&q=80"
    "doctor-Female.jpg" = "https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=1920&q=80"
    "pharmacist-Male.jpg" = "https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=1920&q=80"
    "pharmacist-Female.jpg" = "https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=1920&q=80"
    "lab_technician-Male.jpg" = "https://images.unsplash.com/photo-1532187863486-abf9dbad1b69?w=1920&q=80"
    "lab_technician-Female.jpg" = "https://images.unsplash.com/photo-1579154204601-01588f351e67?w=1920&q=80"
    "receptionist-Male.jpg" = "https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=1920&q=80"
    "receptionist-Female.jpg" = "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=1920&q=80"
}

foreach ($key in $images.Keys) {
    Write-Host "Downloading $key..."
    Invoke-WebRequest -Uri $images[$key] -OutFile "c:\xampp7\htdocs\HMS_M\assets\img\$key"
}
Write-Host "All downloads complete."
