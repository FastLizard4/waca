<?php
if (!defined("ACC")) {
    die();
} // Invalid entry point

class Request extends DataObject
{
    private $email;
    private $ip;
    private $name;
    private $comment;
    private $status;
    private $date;
    private $checksum;
    private $emailsent;
    private $emailconfirm;
    private $reserved;
    private $useragent;
    private $forwardedip;
    
    private $hasComments = "?";
    private $ipRequests = false;
    private $emailRequests = false;
    private $blacklistCache = null;
    
    public function save()
    {
        if($this->isNew)
		{ // insert
			$statement = $this->dbObject->prepare(
                "INSERT INTO `request` (" . 
                "email, ip, name, comment, status, date, checksum, emailsent, emailconfirm, reserved, useragent, forwardedip" . 
                ") VALUES (" . 
                ":email, :ip, :name, :comment, :status, CURRENT_TIMESTAMP(), :checksum, :emailsent," . 
                ":emailconfirm, :reserved, :useragent, :forwardedip" . 
                ");");
			$statement->bindValue(":email", $this->email);
			$statement->bindValue(":ip", $this->ip);
			$statement->bindValue(":name", $this->name);
			$statement->bindValue(":comment", $this->comment);
			$statement->bindValue(":status", $this->status);
			$statement->bindValue(":checksum", $this->checksum);
			$statement->bindValue(":emailsent", $this->emailsent);
			$statement->bindValue(":emailconfirm", $this->emailconfirm);
			$statement->bindValue(":reserved", $this->reserved);
			$statement->bindValue(":useragent", $this->useragent);
			$statement->bindValue(":forwardedip", $this->forwardedip);
			if($statement->execute())
			{
				$this->isNew = false;
				$this->id = $this->dbObject->lastInsertId();
			}
			else
			{
				throw new Exception($statement->errorInfo());
			}
		}
		else
		{ // update
			$statement = $this->dbObject->prepare("UPDATE `request` SET " . 
                "status = :status, checksum = :checksum, emailsent = :emailsent, emailconfirm = :emailconfirm, " .
                "reserved = :reserved " .
                "WHERE id = :id LIMIT 1;");
			$statement->bindValue(":id", $this->id);
			$statement->bindValue(":status", $this->status);
			$statement->bindValue(":checksum", $this->checksum);
			$statement->bindValue(":emailsent", $this->emailsent);
			$statement->bindValue(":emailconfirm", $this->emailconfirm);
			$statement->bindValue(":reserved", $this->reserved);  
			if(!$statement->execute())
			{
				throw new Exception($statement->errorInfo());
			}
		} 
        
    }
    
    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getIp()
    {
        return $this->ip;
    }
    
    public function getTrustedIp()
    {
        return trim(getTrustedClientIP($this->ip, $this->forwardedip));
    }

    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
    }
    
    public function getStatus()
    {
        return $this->status;
    }
    
    public function setStatus($status)
    {
        $this->status = $status;   
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date)
    {
        $this->date = $date;
    }

    public function getChecksum()
    {
        return $this->checksum;
    }

    public function setChecksum($checksum)
    {
        $this->checksum = $checksum;
    }

    public function updateChecksum()
    {
        $this->checksum = md5($this->id . $this->name . $this->email . microtime());
    }
    
    public function getEmailSent()
    {
        return $this->emailsent;
    }

    public function setEmailSent($emailsent)
    {
        $this->emailsent = $emailsent;
    }

    public function getEmailConfirm()
    {
        return $this->emailconfirm;
    }

    public function setEmailConfirm($emailconfirm)
    {
        $this->emailconfirm = $emailconfirm;
    }

    public function getReserved()
    {
        return $this->reserved;
    }
    
    public function getReservedObject()
    {
        return User::getById($this->reserved, $this->dbObject);   
    }

    public function setReserved($reserved)
    {
        $this->reserved = $reserved;
    }

    public function getUserAgent()
    {
        return $this->useragent;
    }

    public function setUserAgent($useragent)
    {
        $this->useragent = $useragent;
    }

    public function getForwardedIp()
    {
        return $this->forwardedip;
    }

    public function setForwardedIp($forwardedip)
    {
        $this->forwardedip = $forwardedip;
    }

    public function hasComments()
    {
        if($this->hasComments !== "?")
        {
            return $this->hasComments;   
        }
        
        if($this->comment != "")
        {
            $this->hasComments = true;
            return true;
        }
        
        $commentsQuery = $this->dbObject->prepare("SELECT COUNT(*) as num FROM comment where request = :id;");
        $commentsQuery->bindValue(":id", $this->id);
        
        $commentsQuery->execute();
        
        $this->hasComments = ($commentsQuery->fetchColumn() != 0);
        return $this->hasComments;
    }
    
    public function getRelatedEmailRequests()
    {
        if($this->emailRequests == false)
        {
            global $cDataClearEmail;
            
            $query = $this->dbObject->prepare("SELECT * FROM request WHERE email = :email AND email != :clearedemail AND id != :id AND emailconfirm = 'Confirmed';");
            $query->bindValue(":id", $this->id);
            $query->bindValue(":email", $this->email);
            $query->bindValue(":clearedemail", $cDataClearEmail);
            
            $query->execute();
            
            $this->emailRequests = $query->fetchAll(PDO::FETCH_CLASS, "Request");
            
            foreach($this->emailRequests as $r)
            {
                $r->setDatabase($this->dbObject);   
            }
        }
        
        return $this->emailRequests;
    }
        
    public function getRelatedIpRequests()
    {
        if($this->ipRequests == false)
        {
            global $cDataClearIp;
            
            $query = $this->dbObject->prepare("SELECT * FROM request WHERE (ip = :ip OR forwardedip LIKE :forwarded) AND ip != :clearedip AND id != :id AND emailconfirm = 'Confirmed';");
            
            $trustedIp = $this->getTrustedIp();
            $trustedFilter = '%' . $trustedIp . '%';
                        
            $query->bindValue(":id", $this->id);
            $query->bindValue(":ip", $trustedIp);
            $query->bindValue(":forwarded", $trustedFilter);
            $query->bindValue(":clearedip", $cDataClearIp);
            
            $query->execute();
            
            $this->ipRequests = $query->fetchAll(PDO::FETCH_CLASS, "Request");
            
            foreach($this->emailRequests as $r)
            {
                $r->setDatabase($this->dbObject);   
            }
        }
        
        return $this->ipRequests;
    }
    
    public function isBlacklisted()
    {
        global $enableTitleBlacklist;
        
        if(! $enableTitleBlacklist || $this->blacklistCache === false)
        {
            return false;
        }
        
        $apiResult = file_get_contents("https://en.wikipedia.org/w/api.php?action=titleblacklist&tbtitle=" . urlencode($this->name) . "&tbaction=new-account&tbnooverride&format=php");
        
        $data = unserialize($apiResult);
        
        $result = $data['titleblacklist']['result'] == "ok";
        
        $this->blacklistCache = $result ? false : $data['titleblacklist']['line'];
        
        return $this->blacklistCache;
    }
    
    public function getComments()
    {
        return Comment::getForRequest($this->id, $this->dbObject);   
    }
    
    public function isProtected()
    {
        global $protectReservedRequests;

        if(!$protectReservedRequests) 
        {
            return false;
        }
        
        $reservedTo = $this->getReserved();

        if($this->reserved != 0)
        {
            if($this->reserved == User::getCurrent()->getId())
            {
                return false;
            }
            else
            {
                return true;
            }
        } 
        else 
        {
            return false;
        }

    }   
}
