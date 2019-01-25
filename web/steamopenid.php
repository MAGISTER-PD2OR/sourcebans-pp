<?php
/*************************************************************************
This file is part of SourceBans++

Copyright � 2014-2016 SourceBans++ Dev Team <https://github.com/sbpp>

SourceBans++ is licensed under a
Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License.

You should have received a copy of the license along with this
work.  If not, see <http://creativecommons.org/licenses/by-nc-sa/3.0/>.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

This program is based off work covered by the following copyright(s):
SourceBans 1.4.11
Copyright � 2007-2014 SourceBans Team - Part of GameConnect
Licensed under CC BY-NC-SA 3.0
Page: <http://www.sourcebans.net/> - <http://www.gameconnect.net/>
*************************************************************************/
// Steam Login by @duhowpi 2015

include_once 'init.php';
include_once 'config.php';
require_once 'includes/openid.php';

function steamOauth()
{
    $openid = new LightOpenID(Host::complete());
    if (!$openid->mode) {
        $openid->identity = 'https://steamcommunity.com/openid';
        header("Location: " . $openid->authUrl());
        exit();
    }
    if ($openid->validate()) {
        $ids = $openid->identity;
        $ptn = "/^https:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/";
        preg_match($ptn, $ids, $matches);

        if (!empty($matches[1])) {
            return $matches[1];
        }
    }
    return false;
}

$data = steamOauth();

if ($data !== false) {
    $steamid = \SteamID\SteamID::toSteam2($data);
    $GLOBALS['PDO']->query('SELECT aid, password FROM `:prefix_admins` WHERE authid = :authid');
    $GLOBALS['PDO']->bind(':authid', $steamid);
    $result = $GLOBALS['PDO']->single();
    if (count($result) == 2) {
        global $userbank;
        if (empty($result['password']) || $result['password'] == $userbank->encrypt_password('') || $result['password'] == $userbank->hash('')) {
            header("Location: ".Host::complete()."/index.php?p=login&m=empty_pwd");
            die;
        } else {
            setcookie('remember_me', 604800, time() + 604800);
            $_SESSION['aid'] = $result['aid'];
        }
    }
} else {
    header("Location: ".Host::complete()."/index.php?p=login&m=steam_failed");
}
header("Location: ".Host::complete());
