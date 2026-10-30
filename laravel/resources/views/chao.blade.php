<style>
    h1 { color: red; }
    a { color: #1d69ec; 
        text-decoration: none;
}

.btn {
  color: blue;
  text-transform: uppercase;
  text-decoration: none;
  border: 2px solid #2c719f;
  padding: 10px 20px;
  font-size: 17px;
  cursor: pointer;
  font-weight: bold;
  background: transparent;
  position: relative;
  transition: all 1s;
  overflow: hidden;
}

.btn:hover {
  color: white;
}

.btn::before {
  content: "";
  position: absolute;
  height: 100%;
  width: 0%;
  top: 0;
  left: -40px;
  transform: skewX(45deg);
  background-color: #57BDFF;
  z-index: -1;
  transition: all 1s;
}

.btn:hover::before {
  width: 160%;
}

</style>

<h1>CHAOO</h1>

<button class="btn"><a href="/hola">HOLAAA</a></button>